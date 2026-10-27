export const marketingToolGroups = [
  { id: "paid-media", name: "Paid Media", category: "Paid Media" },
  { id: "seo", name: "SEO & AI Search", category: "SEO & AI Search" },
  { id: "content", name: "Content & Social", category: "Content" },
  {
    id: "conversion",
    name: "Growth & Conversion",
    category: "Growth & Conversion",
  },
  {
    id: "strategy",
    name: "Strategy & Research",
    category: "Strategy & Research",
  },
  { id: "email", name: "Email & Lifecycle", category: "Email & Lifecycle" },
  { id: "analytics", name: "Analytics", category: "Analytics" },
  { id: "creative", name: "Creative & Design", category: "Creative & Design" },
  {
    id: "reporting",
    name: "Reports & Deliverables",
    category: "Reporting & Deliverables",
  },
] as const;
export type MarketingToolGroupId = (typeof marketingToolGroups)[number]["id"];
export function marketingToolGroup(category: string) {
  return marketingToolGroups.find((group) => group.category === category);
}
