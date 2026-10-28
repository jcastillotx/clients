import importlib.util
import json
from pathlib import Path
import unittest
from unittest.mock import patch

SPEC = importlib.util.spec_from_file_location("release_deploy", Path(__file__).parents[2] / "scripts/release/deploy.py")
deploy = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(deploy)
COMMIT = "a" * 40
RELEASE = COMMIT + "-123-1"
DIGEST = "b" * 64
URL = f"https://{deploy.BUCKET}.s3.us-west-2.amazonaws.com/clients/releases/{RELEASE}/image.tar.gz?X-Amz-Signature=example"
PREVIOUS = {"Id": "previous", "Config": {"Labels": {}}}


class ReleaseSafetyTests(unittest.TestCase):
    def test_rejects_artifacts_from_another_bucket_or_prefix(self):
        for url in [URL.replace(deploy.BUCKET, "other-bucket"), URL.replace("/clients/releases/", "/other/"),
                    URL.replace("https://", "http://"), URL + "#fragment"]:
            with self.subTest(url=url), self.assertRaises(deploy.ReleaseError):
                deploy.validate_release(RELEASE, COMMIT, DIGEST, url)

    def test_accepts_only_matching_commit_and_run_identifiers(self):
        deploy.validate_release(RELEASE, COMMIT, DIGEST, URL)
        with self.assertRaises(deploy.ReleaseError):
            deploy.validate_release("../outside", COMMIT, DIGEST, URL)

    def test_wrong_public_release_is_not_a_successful_health_check(self):
        health = {"data": {"status": "operational", "services": {"database": "ok"}, "release": "old"}}
        with patch.object(deploy, "fetch", return_value=json.dumps(health).encode()):
            with self.assertRaisesRegex(deploy.ReleaseError, "different release"):
                deploy.verify(deploy.PUBLIC, COMMIT)

    def test_health_and_form_assets_are_checked(self):
        health = {"data": {"status": "operational", "services": {"database": "ok"}, "release": COMMIT}}
        page = b'Request a New Project<script src="/_next/static/chunks/app.js"></script>'
        with patch.object(deploy, "fetch", side_effect=[json.dumps(health).encode(), page, page, b"js"]) as fetch:
            deploy.verify(deploy.PUBLIC, COMMIT)
            self.assertEqual(fetch.call_args_list[-1].args, (deploy.PUBLIC + "/_next/static/chunks/app.js",))

    def test_public_check_failure_restores_previous_after_private_check_passes(self):
        with patch.object(deploy, "inspect", return_value=PREVIOUS), \
                patch.object(deploy, "docker"), patch.object(deploy, "run_container"), \
                patch.object(deploy, "wait_ready", side_effect=[{}, deploy.ReleaseError("public failure")]), \
                patch.object(deploy, "restore_previous") as restore:
            with self.assertRaisesRegex(deploy.PromotionError, "public failure") as result:
                deploy.promote(PREVIOUS, "candidate", "image", Path("runtime.env"), COMMIT, RELEASE)
            self.assertEqual(result.exception.rollback, "restored")
            restore.assert_called_once_with(PREVIOUS, "clients-rollback-" + RELEASE, COMMIT)

    def test_failed_rollback_is_reported_as_requiring_operator_recovery(self):
        with patch.object(deploy, "inspect", return_value=PREVIOUS), \
                patch.object(deploy, "docker"), patch.object(deploy, "run_container"), \
                patch.object(deploy, "wait_ready", side_effect=deploy.ReleaseError("not ready")), \
                patch.object(deploy, "restore_previous", side_effect=deploy.ReleaseError("missing")):
            with self.assertRaises(deploy.PromotionError) as result:
                deploy.promote(PREVIOUS, "candidate", "image", Path("runtime.env"), COMMIT, RELEASE)
            self.assertEqual(result.exception.rollback, "failed")

    def test_success_retains_previous_container_without_rollback(self):
        with patch.object(deploy, "inspect", return_value=PREVIOUS), \
                patch.object(deploy, "docker") as docker, patch.object(deploy, "run_container"), \
                patch.object(deploy, "wait_ready"), patch.object(deploy, "restore_previous") as restore:
            name = deploy.promote(PREVIOUS, "candidate", "image", Path("runtime.env"), COMMIT, RELEASE)
            self.assertEqual(name, "clients-rollback-" + RELEASE)
            self.assertIn(unittest.mock.call("rename", deploy.ACTIVE, name), docker.call_args_list)
            restore.assert_not_called()

    def test_changed_live_container_is_not_stopped(self):
        with patch.object(deploy, "inspect", return_value={"Id": "other"}), \
                patch.object(deploy, "docker") as docker:
            with self.assertRaisesRegex(deploy.ReleaseError, "changed"):
                deploy.promote(PREVIOUS, "candidate", "image", Path("runtime.env"), COMMIT, RELEASE)
            docker.assert_not_called()

    def test_rollback_does_not_delete_an_unrelated_container(self):
        unrelated = {"Id": "other", "Config": {"Labels": {"org.opencontainers.image.revision": "other"}}}
        with patch.object(deploy, "inspect", return_value=unrelated), patch.object(deploy, "docker") as docker:
            with self.assertRaisesRegex(deploy.ReleaseError, "Unexpected active"):
                deploy.restore_previous(PREVIOUS, "rollback", COMMIT)
            docker.assert_not_called()


if __name__ == "__main__":
    unittest.main()
