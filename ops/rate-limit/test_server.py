import concurrent.futures
import importlib.util
import tempfile
import time
import unittest
from pathlib import Path

spec = importlib.util.spec_from_file_location('rate_server', Path(__file__).with_name('server.py'))
server = importlib.util.module_from_spec(spec)
spec.loader.exec_module(server)

class SharedCounterTests(unittest.TestCase):
    def test_atomic_limit_across_independent_connections(self):
        with tempfile.TemporaryDirectory() as directory:
            db = str(Path(directory) / 'counter.db')
            server.consume('initialize', 1, 1000, db)
            with concurrent.futures.ThreadPoolExecutor(max_workers=12) as pool:
                results = list(pool.map(lambda _: server.consume('same-client', 5, 60000, db), range(40)))
            self.assertEqual(sum(r['success'] for r in results), 5)
            self.assertFalse(server.consume('same-client', 5, 60000, db)['success'])
            self.assertTrue(server.consume('other-client', 5, 60000, db)['success'])

    def test_persisted_counter_and_expiry(self):
        with tempfile.TemporaryDirectory() as directory:
            db = str(Path(directory) / 'counter.db')
            self.assertTrue(server.consume('key', 1, 30, db)['success'])
            self.assertFalse(server.consume('key', 1, 30, db)['success'])
            time.sleep(.05)
            self.assertTrue(server.consume('key', 1, 30, db)['success'])


class HttpCounterTests(unittest.TestCase):
    def test_authentication_validation_and_shared_http_counter(self):
        import json
        import threading
        from urllib.request import Request, urlopen
        from urllib.error import HTTPError
        from unittest.mock import patch
        with tempfile.TemporaryDirectory() as directory, patch.object(server, 'DATABASE', str(Path(directory) / 'http.db')), patch.object(server, 'TOKEN', 'private-test-token'):
            http = server.ThreadingHTTPServer(('127.0.0.1', 0), server.Handler)
            thread = threading.Thread(target=http.serve_forever, daemon=True)
            thread.start()
            url = 'http://127.0.0.1:' + str(http.server_port) + '/limit'
            def request(payload, token='private-test-token'):
                req = Request(url, data=json.dumps(payload).encode(), headers={'Authorization': 'Bearer '+token, 'Content-Type':'application/json'})
                try:
                    with urlopen(req) as response: return response.status, json.load(response)
                except HTTPError as response: return response.code, json.load(response)
            try:
                payload = {'key':'a'*64, 'limit':2, 'windowMs':60000}
                self.assertEqual(request(payload, 'wrong')[0], 401)
                self.assertEqual(request({**payload, 'windowMs':0})[0], 400)
                self.assertEqual(request({**payload, 'limit':True})[0], 400)
                self.assertTrue(request(payload)[1]['success'])
                self.assertTrue(request(payload)[1]['success'])
                self.assertFalse(request(payload)[1]['success'])
            finally:
                http.shutdown()
                http.server_close()
                thread.join()

if __name__ == '__main__': unittest.main()
