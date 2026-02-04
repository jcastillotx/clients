/**
 * Session Operations - Memory Management Examples
 *
 * Demonstrates session lifecycle memory operations for cross-session continuity.
 */

// Session Start - Restore Context
const sessionStart = {
  operation: "restore",
  command: "npx claude-flow@alpha hooks session-restore --session-id 'previous-session-id'",
  manualRetrieve: {
    key: "session-last-state",
    namespace: "sessions"
  },
  actions: [
    "Retrieve previous session state",
    "Load agent profiles",
    "Restore task history",
    "Initialize performance tracking"
  ]
};

// During Session - Incremental Storage
const sessionDuringWork = {
  operation: "store-incremental",
  examples: [
    {
      trigger: "After completing a task",
      memory: {
        key: "session-task-completion",
        namespace: "sessions",
        value: {
          taskId: "task-123",
          taskType: "blade-component-conversion",
          agent: "coder",
          duration: "12min",
          filesModified: ["app.blade.php", "navbar.blade.php"],
          outcome: "success",
          learnings: [
            "Tailwind utility classes work well for navbar",
            "Alpine.js dropdown requires x-data initialization"
          ]
        },
        tags: ["task", "completion", "blade"],
        ttl: 86400  // 24 hours
      }
    },
    {
      trigger: "After discovering a pattern",
      memory: {
        key: "discovery-pattern-" + Date.now(),
        namespace: "project",
        value: {
          pattern: "Blade component prop passing",
          discovery: "Use :prop syntax for dynamic values, prop for static",
          example: "<x-button :disabled='$isDisabled' label='Submit' />",
          applicability: "All Blade components",
          impact: "High - affects component design"
        },
        tags: ["discovery", "blade", "pattern"]
      }
    },
    {
      trigger: "After agent coordination",
      memory: {
        key: "coordination-success",
        namespace: "performance",
        value: {
          topology: "mesh",
          agents: ["coder", "reviewer", "tester"],
          coordinationMethod: "hooks + memory",
          outcome: "Successful parallel execution",
          timesSaved: "18min (vs sequential)",
          efficiency: 0.92
        },
        tags: ["coordination", "performance"]
      }
    }
  ]
};

// Session End - Full State Persistence
const sessionEnd = {
  operation: "persist",
  command: "npx claude-flow@alpha hooks session-end --export-metrics true",
  manualStore: {
    key: "session-" + Date.now(),
    namespace: "sessions",
    value: {
      sessionId: "sess-" + Date.now(),
      startTime: "2026-02-03T10:00:00Z",
      endTime: "2026-02-03T11:30:00Z",
      duration: "90min",
      summary: "Implemented cross-session memory system with full documentation",
      agents: [
        {
          name: "coder",
          tasksCompleted: 8,
          filesModified: 12,
          successRate: 0.95
        },
        {
          name: "reviewer",
          reviewsCompleted: 5,
          issuesFound: 3,
          successRate: 0.90
        },
        {
          name: "tester",
          testsWritten: 15,
          coverage: 0.88,
          successRate: 0.93
        }
      ],
      achievements: [
        "Memory system fully documented",
        "Example code provided",
        "Test suite created",
        "Performance baseline established"
      ],
      metrics: {
        tokensUsed: 45000,
        tokensSaved: 32.3,
        speedImprovement: 2.8,
        agentEfficiency: 0.93
      },
      nextSteps: [
        "Test session restoration",
        "Monitor memory usage",
        "Refine agent profiles",
        "Optimize coordination patterns"
      ]
    },
    tags: ["completed", "laravel", "memory-system"]
  }
};

// Memory Search Examples
const memorySearchExamples = [
  {
    query: "laravel blade component patterns",
    namespace: "project",
    limit: 10,
    purpose: "Find previous component implementations"
  },
  {
    query: "tailwind conversion challenges",
    namespace: "sessions",
    limit: 5,
    purpose: "Learn from past conversion issues"
  },
  {
    query: "agent coordination success stories",
    namespace: "performance",
    limit: 8,
    purpose: "Identify best coordination patterns"
  }
];

// Memory List Examples
const memoryListExamples = [
  {
    namespace: "sessions",
    limit: 20,
    purpose: "View all previous sessions"
  },
  {
    namespace: "agents",
    limit: 10,
    purpose: "Review agent profiles and metrics"
  },
  {
    namespace: "project",
    limit: 15,
    purpose: "Browse project patterns and learnings"
  }
];

// Memory Cleanup Examples
const memoryCleanupExamples = [
  {
    operation: "delete-old-sessions",
    criteria: "Sessions older than 30 days",
    approach: "List sessions, filter by date, batch delete"
  },
  {
    operation: "delete-temporary-data",
    criteria: "Entries with expired TTL",
    approach: "Automatic cleanup by system"
  },
  {
    operation: "archive-completed-projects",
    criteria: "Project entries for completed work",
    approach: "Export to backup, then delete from memory"
  }
];

// Export for documentation
module.exports = {
  sessionStart,
  sessionDuringWork,
  sessionEnd,
  memorySearchExamples,
  memoryListExamples,
  memoryCleanupExamples
};

// Usage Instructions:
// 1. At session start: Use sessionStart.command or retrieve manually
// 2. During session: Store learnings incrementally using sessionDuringWork examples
// 3. At session end: Use sessionEnd.command or store manually
// 4. Search memory: Use memorySearchExamples patterns
// 5. List memory: Use memoryListExamples patterns
// 6. Clean up: Use memoryCleanupExamples approaches
