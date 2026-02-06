import { pgTable, uuid, text, timestamp, boolean, jsonb, integer, decimal, primaryKey } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";

/**
 * Priority enum
 */
export const taskPriorityEnum = ["low", "normal", "high", "urgent"] as const;
export type TaskPriority = (typeof taskPriorityEnum)[number];

/**
 * Staff Task Boards table
 *
 * Main container for organizing tasks in a Kanban-style board
 */
export const staffTaskBoards = pgTable("staff_task_boards", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  teamId: uuid("team_id"),
  createdBy: uuid("created_by").references(() => users.id),
  isDefault: boolean("is_default").default(false).notNull(),
  isArchived: boolean("is_archived").default(false).notNull(),
  color: text("color").default("#3b82f6"),
  columnOrder: jsonb("column_order").$type<string[]>().default([]),
  settings: jsonb("settings").$type<{
    allowSubtasks?: boolean;
    autoArchiveCompleted?: boolean;
    defaultWipLimit?: number;
  }>(),
  sortOrder: integer("sort_order").default(0).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Task Columns table
 *
 * Represents columns within a board (e.g., "To Do", "In Progress", "Done")
 */
export const staffTaskColumns = pgTable("staff_task_columns", {
  id: uuid("id").primaryKey().defaultRandom(),
  boardId: uuid("board_id")
    .notNull()
    .references(() => staffTaskBoards.id, { onDelete: "cascade" }),
  name: text("name").notNull(),
  position: integer("position").default(0).notNull(),
  wipLimit: integer("wip_limit"),
  color: text("color").default("#94a3b8"),
  icon: text("icon"),
  isDoneColumn: boolean("is_done_column").default(false).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Tasks table
 *
 * Individual tasks within a board and column
 */
export const staffTasks = pgTable("staff_tasks", {
  id: uuid("id").primaryKey().defaultRandom(),
  boardId: uuid("board_id")
    .notNull()
    .references(() => staffTaskBoards.id, { onDelete: "cascade" }),
  columnId: uuid("column_id")
    .notNull()
    .references(() => staffTaskColumns.id, { onDelete: "cascade" }),
  parentId: uuid("parent_id").references((): any => staffTasks.id),
  title: text("title").notNull(),
  description: text("description"),
  priority: text("priority", { enum: taskPriorityEnum }).default("normal").notNull(),
  startDate: timestamp("start_date", { withTimezone: true }),
  dueDate: timestamp("due_date", { withTimezone: true }),
  estimatedHours: decimal("estimated_hours", { precision: 10, scale: 2 }),
  actualHours: decimal("actual_hours", { precision: 10, scale: 2 }),
  progress: integer("progress").default(0).notNull(),
  position: integer("position").default(0).notNull(),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  reporterId: uuid("reporter_id").references(() => users.id),
  clientId: uuid("client_id"),
  requestId: uuid("request_id"),
  completedAt: timestamp("completed_at", { withTimezone: true }),
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    attachments?: Array<{ name: string; url: string; size: number }>;
    customFields?: Record<string, any>;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Task Checklists table
 *
 * Checklist items within a task
 */
export const staffTaskChecklists = pgTable("staff_task_checklists", {
  id: uuid("id").primaryKey().defaultRandom(),
  taskId: uuid("task_id")
    .notNull()
    .references(() => staffTasks.id, { onDelete: "cascade" }),
  title: text("title").notNull(),
  isCompleted: boolean("is_completed").default(false).notNull(),
  position: integer("position").default(0).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Task Comments table
 *
 * Comments and activity log for tasks
 */
export const staffTaskComments = pgTable("staff_task_comments", {
  id: uuid("id").primaryKey().defaultRandom(),
  taskId: uuid("task_id")
    .notNull()
    .references(() => staffTasks.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id),
  content: text("content").notNull(),
  isSystem: boolean("is_system").default(false).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Task Labels table
 *
 * Labels/tags that can be applied to tasks
 */
export const staffTaskLabels = pgTable("staff_task_labels", {
  id: uuid("id").primaryKey().defaultRandom(),
  boardId: uuid("board_id").references(() => staffTaskBoards.id, { onDelete: "cascade" }),
  name: text("name").notNull(),
  color: text("color").notNull(),
  description: text("description"),
  isGlobal: boolean("is_global").default(false).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Task Assignees junction table
 *
 * Many-to-many relationship between tasks and users
 */
export const staffTaskAssignees = pgTable("staff_task_assignees", {
  id: uuid("id").primaryKey().defaultRandom(),
  taskId: uuid("task_id")
    .notNull()
    .references(() => staffTasks.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id, { onDelete: "cascade" }),
  role: text("role"),
  assignedAt: timestamp("assigned_at", { withTimezone: true }).defaultNow().notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Task Label junction table
 *
 * Many-to-many relationship between tasks and labels
 */
export const staffTaskLabelRelations = pgTable(
  "staff_task_label_relations",
  {
    taskId: uuid("task_id")
      .notNull()
      .references(() => staffTasks.id, { onDelete: "cascade" }),
    labelId: uuid("label_id")
      .notNull()
      .references(() => staffTaskLabels.id, { onDelete: "cascade" }),
  },
  (table) => ({
    pk: primaryKey({ columns: [table.taskId, table.labelId] }),
  }),
);

/**
 * Relations
 */
export const staffTaskBoardsRelations = relations(staffTaskBoards, ({ one, many }) => ({
  creator: one(users, {
    fields: [staffTaskBoards.createdBy],
    references: [users.id],
  }),
  columns: many(staffTaskColumns),
  tasks: many(staffTasks),
  labels: many(staffTaskLabels),
}));

export const staffTaskColumnsRelations = relations(staffTaskColumns, ({ one, many }) => ({
  board: one(staffTaskBoards, {
    fields: [staffTaskColumns.boardId],
    references: [staffTaskBoards.id],
  }),
  tasks: many(staffTasks),
}));

export const staffTasksRelations = relations(staffTasks, ({ one, many }) => ({
  board: one(staffTaskBoards, {
    fields: [staffTasks.boardId],
    references: [staffTaskBoards.id],
  }),
  column: one(staffTaskColumns, {
    fields: [staffTasks.columnId],
    references: [staffTaskColumns.id],
  }),
  parent: one(staffTasks, {
    fields: [staffTasks.parentId],
    references: [staffTasks.id],
  }),
  creator: one(users, {
    fields: [staffTasks.createdBy],
    references: [users.id],
  }),
  reporter: one(users, {
    fields: [staffTasks.reporterId],
    references: [users.id],
  }),
  subtasks: many(staffTasks),
  checklists: many(staffTaskChecklists),
  comments: many(staffTaskComments),
  assignees: many(staffTaskAssignees),
  labelRelations: many(staffTaskLabelRelations),
}));

export const staffTaskChecklistsRelations = relations(staffTaskChecklists, ({ one }) => ({
  task: one(staffTasks, {
    fields: [staffTaskChecklists.taskId],
    references: [staffTasks.id],
  }),
}));

export const staffTaskCommentsRelations = relations(staffTaskComments, ({ one }) => ({
  task: one(staffTasks, {
    fields: [staffTaskComments.taskId],
    references: [staffTasks.id],
  }),
  user: one(users, {
    fields: [staffTaskComments.userId],
    references: [users.id],
  }),
}));

export const staffTaskLabelsRelations = relations(staffTaskLabels, ({ one, many }) => ({
  board: one(staffTaskBoards, {
    fields: [staffTaskLabels.boardId],
    references: [staffTaskBoards.id],
  }),
  taskRelations: many(staffTaskLabelRelations),
}));

export const staffTaskAssigneesRelations = relations(staffTaskAssignees, ({ one }) => ({
  task: one(staffTasks, {
    fields: [staffTaskAssignees.taskId],
    references: [staffTasks.id],
  }),
  user: one(users, {
    fields: [staffTaskAssignees.userId],
    references: [users.id],
  }),
}));

export const staffTaskLabelRelationsRelations = relations(staffTaskLabelRelations, ({ one }) => ({
  task: one(staffTasks, {
    fields: [staffTaskLabelRelations.taskId],
    references: [staffTasks.id],
  }),
  label: one(staffTaskLabels, {
    fields: [staffTaskLabelRelations.labelId],
    references: [staffTaskLabels.id],
  }),
}));

/**
 * TypeScript types
 */
export type StaffTaskBoard = typeof staffTaskBoards.$inferSelect;
export type NewStaffTaskBoard = typeof staffTaskBoards.$inferInsert;

export type StaffTaskColumn = typeof staffTaskColumns.$inferSelect;
export type NewStaffTaskColumn = typeof staffTaskColumns.$inferInsert;

export type StaffTask = typeof staffTasks.$inferSelect;
export type NewStaffTask = typeof staffTasks.$inferInsert;

export type StaffTaskChecklist = typeof staffTaskChecklists.$inferSelect;
export type NewStaffTaskChecklist = typeof staffTaskChecklists.$inferInsert;

export type StaffTaskComment = typeof staffTaskComments.$inferSelect;
export type NewStaffTaskComment = typeof staffTaskComments.$inferInsert;

export type StaffTaskLabel = typeof staffTaskLabels.$inferSelect;
export type NewStaffTaskLabel = typeof staffTaskLabels.$inferInsert;

export type StaffTaskAssignee = typeof staffTaskAssignees.$inferSelect;
export type NewStaffTaskAssignee = typeof staffTaskAssignees.$inferInsert;
