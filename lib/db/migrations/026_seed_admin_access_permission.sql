INSERT INTO public.permissions (name, description, resource, action)
VALUES ('admin.access', 'Access the administration area', 'admin', 'access')
ON CONFLICT (name) DO UPDATE
SET
  description = EXCLUDED.description,
  resource = EXCLUDED.resource,
  action = EXCLUDED.action;

INSERT INTO public.role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM public.roles
CROSS JOIN public.permissions
WHERE roles.name IN ('admin', 'super_admin')
  AND permissions.name = 'admin.access'
ON CONFLICT (role_id, permission_id) DO NOTHING;
