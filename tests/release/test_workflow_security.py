"""Prevent regressions in credentials and action supply-chain controls."""
from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[2]


class WorkflowSecurityTests(unittest.TestCase):
    def test_all_workflow_actions_use_immutable_revisions(self):
        for path in (ROOT / '.github/workflows').glob('*.yml'):
            with self.subTest(path=path.name):
                actions = re.findall(r'^\s*(?:-\s*)?uses:\s*(\S+)', path.read_text(), re.M)
                self.assertTrue(actions)
                for action in actions:
                    self.assertRegex(action, r'^[^@\s]+@[a-f0-9]{40}$')

    def test_checkout_does_not_leave_push_credentials(self):
        for path in (ROOT / '.github/workflows').glob('*.yml'):
            text = path.read_text()
            self.assertEqual(text.count('uses: actions/checkout@'), text.count('persist-credentials: false'), path.name)
            self.assertIn('permissions:\n  contents: read', text)

    def test_production_identity_is_only_available_after_build(self):
        text = (ROOT / '.github/workflows/deploy.yml').read_text()
        build, release = text.split('\n  release:', 1)
        self.assertNotIn('id-token: write', build)
        self.assertIn('needs: build', release)
        self.assertIn('id-token: write', release)
        self.assertIn('test "$GITHUB_REF" = refs/heads/main', build)


if __name__ == '__main__':
    unittest.main()
