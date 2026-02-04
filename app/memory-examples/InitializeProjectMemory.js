/**
 * Initialize Project Memory - Laravel/Tailwind Project
 *
 * This script demonstrates how to initialize cross-session memory
 * for the Laravel + Tailwind CSS project.
 */

// Store Project Context
const projectContext = {
  key: "laravel-tailwind-conversion",
  namespace: "project",
  value: {
    framework: "Laravel + Livewire",
    frontend: "Tailwind CSS (converted from Bootstrap)",
    patterns: {
      layouts: "resources/views/layouts/",
      components: "resources/views/components/",
      livewire: "resources/views/livewire/",
      css: "resources/css/",
      js: "resources/js/"
    },
    completedWork: [
      "Bootstrap removal (AdminLTE)",
      "Tailwind CSS integration",
      "Alpine.js conversion",
      "Component restructuring",
      "Brand styling system"
    ],
    branding: {
      file: "resources/css/brand-tailwind.css",
      variables: ["primary", "secondary", "accent", "text", "background"],
      approach: "CSS custom properties with Tailwind utilities"
    },
    fileStructure: {
      layouts: ["app.blade.php", "guest.blade.php"],
      partials: ["navbar.blade.php", "sidebar.blade.php", "footer.blade.php"],
      components: ["page-header.blade.php", "layouts/app.blade.php"],
      authViews: [
        "login.blade.php",
        "register.blade.php",
        "forgot-password.blade.php",
        "reset-password.blade.php"
      ]
    }
  },
  tags: ["laravel", "tailwind", "livewire", "conversion", "bootstrap-removal"]
};

// Store Agent Profiles
const agentProfiles = [
  {
    key: "agent-coder-laravel-expertise",
    namespace: "agents",
    value: {
      agent: "coder",
      expertise: ["Laravel Blade", "Livewire", "Tailwind CSS", "Alpine.js"],
      successPatterns: [
        "Component-based layouts with x-component syntax",
        "Utility-first styling with Tailwind",
        "Reactive components with Livewire wire: attributes",
        "Alpine.js for client-side interactivity"
      ],
      errorPrevention: [
        "Always preserve Alpine.js x- directives",
        "Maintain Livewire wire: attributes during edits",
        "Check brand color CSS variables before applying",
        "Verify component prop passing with :prop syntax"
      ],
      performanceMetrics: {
        successRate: 0.94,
        specializationLevel: "expert",
        averageTaskTime: "15min"
      }
    },
    tags: ["agent", "coder", "laravel", "tailwind"]
  },
  {
    key: "agent-reviewer-quality-checks",
    namespace: "agents",
    value: {
      agent: "reviewer",
      expertise: ["Code quality", "Security", "Best practices", "Performance"],
      checkPoints: [
        "Tailwind class optimization (avoid duplicates)",
        "Alpine.js syntax correctness",
        "Livewire component lifecycle",
        "Accessibility (ARIA labels, semantic HTML)",
        "Security (CSRF, XSS prevention)"
      ],
      performanceMetrics: {
        successRate: 0.91,
        specializationLevel: "expert",
        averageReviewTime: "10min"
      }
    },
    tags: ["agent", "reviewer", "quality", "security"]
  },
  {
    key: "agent-tester-coverage",
    namespace: "agents",
    value: {
      agent: "tester",
      expertise: ["PHPUnit", "Pest", "Livewire Testing", "Browser Testing"],
      testingApproach: [
        "Unit tests for business logic",
        "Livewire component tests",
        "Browser tests for critical flows",
        "TDD workflow for new features"
      ],
      performanceMetrics: {
        successRate: 0.89,
        specializationLevel: "advanced",
        averageCoverageGoal: 0.85
      }
    },
    tags: ["agent", "tester", "phpunit", "livewire"]
  }
];

// Store Performance Baseline
const performanceBaseline = {
  key: "perf-baseline-session",
  namespace: "performance",
  value: {
    timestamp: Date.now(),
    project: "Laravel/Tailwind Conversion",
    metrics: {
      averageTaskCompletionTime: "30min",
      agentEfficiency: 0.87,
      tokenUsageOptimization: "baseline",
      concurrentOperations: true
    },
    improvements: [
      "Batch file operations in single messages",
      "Parallel agent execution via Task tool",
      "Memory-based context retrieval"
    ],
    goals: {
      tokenReduction: "30%+",
      speedImprovement: "2.5x+",
      agentEfficiency: "0.90+"
    }
  },
  tags: ["performance", "baseline", "optimization"]
};

// Store Common Patterns
const commonPatterns = {
  key: "patterns-laravel-tailwind",
  namespace: "project",
  value: {
    bladeComponents: {
      syntax: "<x-component-name prop='value' />",
      location: "resources/views/components/",
      example: "<x-page-header title='Dashboard' subtitle='Welcome' />"
    },
    tailwindUtilities: {
      layout: "container mx-auto px-4",
      spacing: "space-y-4, gap-4, p-4",
      colors: "bg-brand-primary text-brand-text",
      responsive: "sm:, md:, lg:, xl: prefixes"
    },
    livewireComponents: {
      syntax: "wire:model, wire:click, wire:submit",
      location: "resources/views/livewire/",
      example: "<input wire:model='email' type='email' />"
    },
    alpineDirectives: {
      syntax: "x-data, x-show, x-model, x-on",
      usage: "Client-side interactivity",
      example: "<div x-data='{ open: false }' x-show='open'>"
    }
  },
  tags: ["patterns", "blade", "tailwind", "livewire", "alpine"]
};

// Export for MCP tool usage
module.exports = {
  projectContext,
  agentProfiles,
  performanceBaseline,
  commonPatterns
};

// Usage Instructions:
// Load this module and use the exported objects with MCP memory_store tool:
// mcp__claude-flow_alpha__memory_store(projectContext)
// mcp__claude-flow_alpha__memory_store(agentProfiles[0])
// mcp__claude-flow_alpha__memory_store(performanceBaseline)
// mcp__claude-flow_alpha__memory_store(commonPatterns)
