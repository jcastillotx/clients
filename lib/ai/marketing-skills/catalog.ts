import catalog from "./catalog.json";

export type MarketingSkill = (typeof catalog)[number];
export const marketingSkills: readonly MarketingSkill[] = catalog;
export function getMarketingSkill(id: string) {
  return marketingSkills.find((skill) => skill.id === id);
}
export function marketingSkillSummaries() {
  return marketingSkills.map(({ id, name, category, description }) => ({
    id,
    name,
    category,
    description,
  }));
}
