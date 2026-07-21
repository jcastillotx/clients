import { executeMarketingAgentTask } from "@/lib/ai/marketing-agents/execution";
import { inngest } from "../client";

export const runMarketingAgent = inngest.createFunction(
  {
    id: "marketing-agent-run",
    name: "Run Marketing Agent Workflow",
    retries: 2,
  },
  { event: "marketing-agent/run.requested" },
  async ({ event, step }) => {
    await step.run("execute-marketing-agent-task", async () => {
      await executeMarketingAgentTask(event.data.taskId);
    });

    return {
      taskId: event.data.taskId,
      clientId: event.data.clientId,
      status: "completed",
    };
  },
);
