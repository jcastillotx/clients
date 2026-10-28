#!/usr/bin/env python3
"""Private job-store/configuration backup with verified encrypted S3 retrieval."""
import datetime
import hashlib
import json
import os
from pathlib import Path
import subprocess
import tarfile
import tempfile
import boto3

os.umask(0o077)
root = Path('/opt/clients-jobs')
bucket = 'kre8iv-clients-backups-556348027172-us-west-2'
stamp = datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ')
with tempfile.TemporaryDirectory(prefix='jobs-backup-', dir=root) as temp:
    stage = Path(temp)
    checkpoint = stage / 'checkpoint'
    subprocess.run(['python3', str(root / 'backup.py'), '--project', 'clients-jobs', '--destination', str(checkpoint)], check=True, capture_output=True)
    archive = stage / 'clients-jobs.tar.gz'
    with tarfile.open(archive, 'w:gz') as output:
        output.add(checkpoint, arcname='checkpoint')
        for name in ['.env', 'portal-env.json', 'compose.yml', 'managed.override.yml']:
            path = root / name
            if path.exists():
                output.add(path, arcname='configuration/' + name)
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    key = 'clients/jobs/' + stamp + '/clients-jobs.tar.gz'
    s3 = boto3.client('s3', region_name='us-west-2')
    s3.upload_file(str(archive), bucket, key, ExtraArgs={'ServerSideEncryption': 'AES256', 'Metadata': {'sha256': digest}})
    head = s3.head_object(Bucket=bucket, Key=key)
    retrieved = s3.get_object(Bucket=bucket, Key=key)['Body'].read()
    if hashlib.sha256(retrieved).hexdigest() != digest or head['ContentLength'] != archive.stat().st_size:
        raise RuntimeError('Off-host backup retrieval verification failed')
    report = {'createdAt': stamp, 'bucket': bucket, 'key': key, 'bytes': archive.stat().st_size, 'retrievalVerified': True, 'serverSideEncryption': head['ServerSideEncryption'], 'quiesced': False}
    (root / 'last-backup.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(report))
