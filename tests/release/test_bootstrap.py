import importlib.util
from pathlib import Path
import unittest

SPEC = importlib.util.spec_from_file_location("release_bootstrap", Path(__file__).parents[2] / "scripts/release/bootstrap.py")
bootstrap = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(bootstrap)


class ReleaseAccessTests(unittest.TestCase):
    def test_send_command_is_limited_to_one_document_and_instance(self):
        statements = bootstrap.release_policy()["Statement"]
        command = next(row for row in statements if row["Action"] == "ssm:SendCommand")
        self.assertEqual(len(command["Resource"]), 2)
        self.assertTrue(command["Resource"][0].endswith(":document/Kre8ivClientsRelease"))
        self.assertTrue(command["Resource"][1].endswith(":instance/i-03631eb1405b01b8e"))
        self.assertNotIn("*", command["Resource"])

    def test_artifact_write_access_has_a_project_prefix(self):
        statement = bootstrap.release_policy()["Statement"][0]
        self.assertTrue(statement["Resource"].endswith("/clients/releases/*"))
        self.assertNotIn("iam:PassRole", statement["Action"])

    def test_document_uses_env_interpolation_and_pins_installed_code(self):
        document = bootstrap.ssm_document("a" * 64)
        for value in document["parameters"].values():
            self.assertEqual(value["interpolationType"], "ENV_VAR")
            self.assertIn("allowedPattern", value)
        commands = document["mainSteps"][0]["inputs"]["runCommand"]
        self.assertIn("a" * 64, commands[1])
        self.assertIn("sha256sum -c", commands[1])
        self.assertIn('"$SSM_downloadUrl"', commands[2])
        self.assertNotIn("{{", "\n".join(commands))
