-- Client marketing draft tools. Existing service entitlements are unchanged.
INSERT INTO features (name, display_name, description, category, is_enabled_by_default, requires_setup)
VALUES ('marketing_skill_tools', 'Marketing Skill Library', '48 marketing research, strategy, content and analysis tools. AI drafts require a configured provider.', 'marketing', true, true)
ON CONFLICT (name) DO NOTHING;
