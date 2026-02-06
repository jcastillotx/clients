export interface TemplateContext {
  [key: string]: any;
}

/**
 * Simple template engine for email and document templates
 */
export function compileTemplate(template: string, context: TemplateContext): string {
  return template.replace(/\{\{(.*?)\}\}/g, (match, key) => {
    const trimmedKey = key.trim();
    const value = getNestedValue(context, trimmedKey);
    return value !== undefined ? String(value) : match;
  });
}

function getNestedValue(obj: any, path: string): any {
  return path.split(".").reduce((acc, part) => acc && acc[part], obj);
}

export const TemplateEngine = {
  compile: compileTemplate,
};
