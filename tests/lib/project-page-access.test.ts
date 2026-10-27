import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({
  access: vi.fn(),
  select: vi.fn(),
  limit: vi.fn(),
}));
vi.mock("@/lib/api/resolve-staff-access", () => ({
  resolveStaffAccess: m.access,
}));
vi.mock("@/lib/db", () => ({ db: { select: m.select } }));
import { getAuthorizedProject } from "@/lib/projects/page-access";
const id = "00000000-0000-4000-8000-000000000001";
beforeEach(() => {
  vi.clearAllMocks();
  m.select.mockReturnValue({
    from: () => ({ where: () => ({ limit: m.limit }) }),
  });
  m.limit.mockResolvedValue([{ id, clientId: "own", name: "Fixture project" }]);
});
describe("server-rendered project access", () => {
  it("does not query project data without an authenticated account", async () => {
    m.access.mockResolvedValue(null);
    expect(await getAuthorizedProject(id)).toBeNull();
    expect(m.select).not.toHaveBeenCalled();
  });
  it("does not send malformed IDs to the database", async () => {
    expect(await getAuthorizedProject("not-a-uuid")).toBeNull();
    expect(m.select).not.toHaveBeenCalled();
  });
  it("returns no foreign tenant project even when it exists", async () => {
    m.access.mockResolvedValue({
      clientId: "foreign",
      isAdmin: false,
      isStaff: false,
    });
    expect(await getAuthorizedProject(id)).toBeNull();
  });
  it("allows an ordinary client to see its own project", async () => {
    m.access.mockResolvedValue({
      clientId: "own",
      isAdmin: false,
      isStaff: false,
    });
    expect((await getAuthorizedProject(id))?.id).toBe(id);
  });
  it("retains cross-client access for an authorized staff member", async () => {
    m.access.mockResolvedValue({
      clientId: null,
      isAdmin: false,
      isStaff: true,
    });
    expect((await getAuthorizedProject(id))?.id).toBe(id);
  });
});
