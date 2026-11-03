"""Private authenticated, durable shared fixed-window counters. No client IDs are stored raw."""
from contextlib import closing
import hmac
import json
import os
import sqlite3
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

DATABASE = os.environ.get('RATE_LIMIT_DATABASE', '/data/limits.sqlite3')
TOKEN = os.environ.get('RATE_LIMIT_TOKEN', '')

def consume(key, limit, window_ms, database=None):
    database = database or DATABASE
    with closing(sqlite3.connect(database, timeout=3)) as connection, connection:
        connection.execute('PRAGMA journal_mode=WAL')
        connection.execute('CREATE TABLE IF NOT EXISTS limits (key TEXT PRIMARY KEY, used INTEGER NOT NULL, reset INTEGER NOT NULL)')
        connection.execute('CREATE INDEX IF NOT EXISTS limits_expiry ON limits(reset)')
        connection.execute('BEGIN IMMEDIATE')
        now = int(time.time() * 1000)
        connection.execute('DELETE FROM limits WHERE reset <= ?', (now,))
        row = connection.execute('SELECT used, reset FROM limits WHERE key = ?', (key,)).fetchone()
        used, reset = row if row else (0, now + window_ms)
        allowed = used < limit
        if allowed:
            used += 1
            connection.execute('INSERT INTO limits VALUES (?, ?, ?) ON CONFLICT(key) DO UPDATE SET used=excluded.used, reset=excluded.reset', (key, used, reset))
        return {'success': allowed, 'remaining': max(0, limit - used), 'resetAt': reset}

class Handler(BaseHTTPRequestHandler):
    def log_message(self, *_args):
        pass

    def reply(self, status, body):
        data = json.dumps(body).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Cache-Control', 'no-store')
        self.send_header('Content-Length', str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def do_GET(self):
        if self.path != '/health':
            return self.reply(404, {'error': 'Not found'})
        try:
            with closing(sqlite3.connect(DATABASE, timeout=2)) as connection:
                connection.execute('SELECT 1').fetchone()
            self.reply(200, {'status': 'ok'})
        except sqlite3.Error:
            self.reply(503, {'status': 'unavailable'})

    def do_POST(self):
        self.connection.settimeout(5)
        if self.path != '/limit':
            return self.reply(404, {'error': 'Not found'})
        if not TOKEN or not hmac.compare_digest(self.headers.get('Authorization', ''), 'Bearer ' + TOKEN):
            return self.reply(401, {'error': 'Unauthorized'})
        try:
            size = int(self.headers.get('Content-Length', '0'))
            if not 0 < size <= 2048:
                return self.reply(400, {'error': 'Invalid size'})
            data = json.loads(self.rfile.read(size))
            key, limit, window = data['key'], data['limit'], data['windowMs']
            if not isinstance(key, str) or len(key) != 64 or any(c not in '0123456789abcdef' for c in key):
                raise ValueError('key')
            if type(limit) is not int or not 1 <= limit <= 10000 or type(window) is not int or not 1000 <= window <= 86400000:
                raise ValueError('bounds')
            self.reply(200, consume(key, limit, window))
        except (ValueError, KeyError, TypeError):
            self.reply(400, {'error': 'Invalid request'})
        except (sqlite3.Error, OSError):
            self.reply(503, {'error': 'Counter store unavailable'})

if __name__ == '__main__':
    if len(TOKEN) < 32:
        raise SystemExit('A private rate-limit token is required')
    ThreadingHTTPServer(('0.0.0.0', 8080), Handler).serve_forever()
