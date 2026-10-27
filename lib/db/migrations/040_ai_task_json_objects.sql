-- Repair only structured AI columns affected by the former double JSON encoder.
-- Existing JSON objects and intentional scalar values are left untouched.
DO $$
DECLARE item record; raw text; decoded jsonb;
BEGIN
  FOR item IN
    SELECT id, input, output, metadata FROM ai_tasks
    WHERE jsonb_typeof(input)='string' OR jsonb_typeof(output)='string' OR jsonb_typeof(metadata)='string'
  LOOP
    IF jsonb_typeof(item.input)='string' THEN
      BEGIN
        raw := item.input #>> '{}'; decoded := raw::jsonb;
        IF jsonb_typeof(decoded)='object' THEN UPDATE ai_tasks SET input=decoded WHERE id=item.id; END IF;
      EXCEPTION WHEN invalid_text_representation THEN NULL; END;
    END IF;
    IF jsonb_typeof(item.output)='string' THEN
      BEGIN
        raw := item.output #>> '{}'; decoded := raw::jsonb;
        IF jsonb_typeof(decoded)='object' THEN UPDATE ai_tasks SET output=decoded WHERE id=item.id; END IF;
      EXCEPTION WHEN invalid_text_representation THEN NULL; END;
    END IF;
    IF jsonb_typeof(item.metadata)='string' THEN
      BEGIN
        raw := item.metadata #>> '{}'; decoded := raw::jsonb;
        IF jsonb_typeof(decoded)='object' THEN UPDATE ai_tasks SET metadata=decoded WHERE id=item.id; END IF;
      EXCEPTION WHEN invalid_text_representation THEN NULL; END;
    END IF;
  END LOOP;
END $$;
