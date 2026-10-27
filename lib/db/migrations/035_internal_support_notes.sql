BEGIN;
DROP POLICY IF EXISTS internal_notes_staff_only ON public.support_ticket_comments;
CREATE POLICY internal_notes_staff_only ON public.support_ticket_comments
AS RESTRICTIVE FOR ALL TO authenticated
USING (is_internal = false OR public.is_staff_or_above())
WITH CHECK (is_internal = false OR public.is_staff_or_above());
NOTIFY pgrst, 'reload schema';
COMMIT;
