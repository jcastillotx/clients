export { wordPressBuildLaunchTemplate } from "./wordpress-build-launch";
export { wordPressCarePlanTemplate } from "./wordpress-care-plan";
export { localSeoLaunchTemplate } from "./local-seo-launch";
export { websiteRedesignSprintTemplate } from "./website-redesign-sprint";

/**
 * Registry of all built-in project task templates.
 * Add new templates to this array as they are created.
 */
import { wordPressBuildLaunchTemplate } from "./wordpress-build-launch";
import { wordPressCarePlanTemplate } from "./wordpress-care-plan";
import { localSeoLaunchTemplate } from "./local-seo-launch";
import { websiteRedesignSprintTemplate } from "./website-redesign-sprint";

export const builtInProjectTemplates = [
  wordPressBuildLaunchTemplate,
  wordPressCarePlanTemplate,
  localSeoLaunchTemplate,
  websiteRedesignSprintTemplate,
];
