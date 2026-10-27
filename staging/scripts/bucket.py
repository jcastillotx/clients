from pathlib import Path
import boto3
root=Path(__file__).resolve().parents[1]
env=dict(l.split('=',1) for l in (root/'.env.staging').read_text().splitlines())
assert env['AWS_S3_ENDPOINT']=='http://127.0.0.1:59000'
s3=boto3.client('s3',endpoint_url=env['AWS_S3_ENDPOINT'],aws_access_key_id=env['AWS_ACCESS_KEY_ID'],aws_secret_access_key=env['AWS_SECRET_ACCESS_KEY'],region_name='us-east-1')
s3.create_bucket(Bucket=env['AWS_S3_BUCKET'])
print('Local synthetic bucket is ready.')
