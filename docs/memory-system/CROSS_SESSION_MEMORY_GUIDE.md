# Cross-Session Memory Guide - Implementation

## Overview

This guide documents the implementation of cross-session memory for the Laravel/Tailwind project using Claude Flow's memory system (sql.js + HNSW backend).

## Memory System Architecture

### Storage Backend
- **Technology**: sql.js + HNSW vector search (150x faster than baseline)
- **Organization**: Namespace-based (sessions, agents, project, performance)
- **Features**: TTL support, tag-based filtering, semantic search

### Memory Namespaces

#### 1. Sessions (`namespace: "sessions"`)
Stores session state, task history, and progress tracking.

#### 2. Agents (`namespace: "agents"`)
Tracks agent profiles, success rates, and specializations.

#### 3. Project (`namespace: "project"`)
Stores project-specific patterns, configurations, and learnings.

#### 4. Performance (`namespace: "performance"`)
Records optimization results, bottlenecks, and metrics.

## Session Lifecycle

### At Session Start
```bash
# Restore previous session
npx claude-flow@alpha hooks session-restore --session-id "previous-session-id"
```

### During Session
Store learnings incrementally as work progresses.

### At Session End
```bash
# Persist session state with metrics
npx claude-flow@alpha hooks session-end --export-metrics true
```

## Memory Operations

### Store Memory
```javascript
mcp__claude-flow_alpha__memory_store({
  key: "session-current-state",
  namespace: "sessions",
  value: { /* data */ },
  tags: ["tag1", "tag2"],
  ttl: 86400  // 24 hours (optional)
})
```

### Retrieve Memory
```javascript
mcp__claude-flow_alpha__memory_retrieve({
  key: "session-current-state",
  namespace: "sessions"
})
```

### Search Memory
```javascript
mcp__claude-flow_alpha__memory_search({
  query: "laravel tailwind conversion patterns",
  namespace: "project",
  limit: 10
})
```

### List Memory
```javascript
mcp__claude-flow_alpha__memory_list({
  namespace: "sessions",
  limit: 20
})
```

### Monitor Usage
```javascript
mcp__ruv-swarm__memory_usage({
  detail: "detailed"
})
```

### Delete Memory
```javascript
mcp__claude-flow_alpha__memory_delete({
  key: "session-old-id",
  namespace: "sessions"
})
```

## Project-Specific Implementation

### Current Project Context
```javascript
{
  framework: "Laravel + Livewire",
  frontend: "Tailwind CSS (converted from Bootstrap)",
  patterns: {
    layouts: "resources/views/layouts/",
    components: "resources/views/components/",
    livewire: "resources/views/livewire/"
  },
  completedWork: [
    "Bootstrap removal",
    "Tailwind integration",
    "Alpine.js conversion",
    "Component restructuring"
  ],
  branding: {
    file: "resources/css/brand-tailwind.css",
    variables: ["primary", "secondary", "accent"]
  }
}
```

### Agent Profiles
- **Coder**: Laravel Blade, Livewire, Tailwind CSS expertise
- **Reviewer**: Code quality, security, best practices
- **Tester**: Unit tests, integration tests, TDD
- **Architect**: System design, component structure

## Privacy & Control

### View All Memory
List all namespaces and their contents for full transparency.

### Backup Memory
```bash
mkdir -p backups/memory
npx claude-flow@alpha hooks session-end --export-metrics true > backups/memory/backup-$(date +%Y%m%d).json
```

### Disable Memory Persistence
```bash
export CLAUDE_FLOW_MEMORY_PERSIST=false
```

### Clear All Memory
Delete entries namespace by namespace as needed.

## Benefits

### 🧠 Contextual Awareness
- Remembers project structure and patterns
- Understands conversion history
- Knows component relationships

### 📈 Cumulative Learning
- Learns from successful edits
- Avoids repeated mistakes
- Improves agent routing

### ⚡ Faster Completion
- No need to re-explain project structure
- Instant access to previous decisions
- Optimized agent selection

### 🎯 Personalized Optimization
- Adapts to coding style
- Learns project preferences
- Customizes workflow patterns

## Verification Checklist

- [ ] Store test data successfully
- [ ] Retrieve test data successfully
- [ ] Monitor memory usage
- [ ] Test session persistence cycle
- [ ] Verify semantic search functionality
- [ ] Confirm namespace organization

## Next Steps

1. Initialize memory with current project context
2. Store agent profiles and learnings
3. Test session persistence cycle
4. Monitor memory usage and benefits
5. Document learnings and patterns

## Support

- Documentation: https://github.com/ruvnet/claude-flow
- Issues: https://github.com/ruvnet/claude-flow/issues
