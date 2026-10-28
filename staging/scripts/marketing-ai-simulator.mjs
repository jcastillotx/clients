import http from "node:http";
const retryFailures = new Set();
const server = http.createServer(async (req, res) => {
  if (req.method !== "POST" || req.url !== "/v1/chat/completions") {
    res.writeHead(404);
    return res.end();
  }
  let raw = "";
  for await (const chunk of req) {
    raw += chunk;
    if (raw.length > 300000) {
      res.writeHead(413);
      return res.end();
    }
  }
  if (req.headers.authorization !== "Bearer isolated-simulator") {
    res.writeHead(401);
    return res.end();
  }
  const body = JSON.parse(raw),
    prompt = body.messages?.find((m) => m.role === "user")?.content || "";
  const retryTag = prompt.match(/SIMULATE_RETRY_ONCE_([a-f0-9-]+)/)?.[1];
  if (retryTag && !retryFailures.has(retryTag)) {
    retryFailures.add(retryTag);
    res.writeHead(503, { "content-type": "application/json" });
    return res.end(
      JSON.stringify({ error: { message: "synthetic first-attempt failure" } }),
    );
  }
  if (prompt.includes("SIMULATE_PROVIDER_ERROR")) {
    res.writeHead(503, { "content-type": "application/json" });
    return res.end(
      JSON.stringify({ error: { message: "simulated unavailable" } }),
    );
  }
  const name = prompt.match(/^TOOL: (.+)/)?.[1] || "Marketing";
  let artifact = {
    title: name + " — isolated QA draft",
    analysis:
      "SIMULATED OUTPUT. This verifies application routing, client isolation, validation, persistence and display. No live account data or real AI analysis was used.",
    recommendations: [
      "Review the supplied methodology and gather the account exports required by the selected tool.",
    ],
    missingData: [
      "Real provider acceptance and client evidence are still required.",
    ],
    sources: ["Synthetic isolated test fixture"],
  };
  if (prompt.startsWith("Return one JSON object with exactly these fields:")) {
    if (prompt.includes("executiveSummary"))
      artifact = {
        title: "SIMULATED campaign draft",
        executiveSummary: "Synthetic workflow acceptance only.",
        objective: "Verify queued marketing workflow execution.",
        targetAudience: "Synthetic test audience",
        keyMessages: ["Review this draft before use."],
        channels: [
          {
            name: "Email",
            role: "Synthetic outreach plan",
            budgetPercentage: 100,
            tactics: ["Review the approved brief."],
          },
        ],
        timeline: [
          {
            phase: "Review",
            startWeek: 1,
            endWeek: 1,
            deliverables: ["Approved draft"],
          },
        ],
        kpis: [
          {
            name: "Draft review",
            target: "One review",
            measurement: "Manual review",
          },
        ],
        risks: ["This is synthetic output."],
      };
    else if (prompt.includes("strategySummary")) {
      const inputText =
        prompt.split("USER_INPUT:\n")[1]?.split("\n\nDRAFT_ARTIFACT:")[0] ||
        "{}";
      const input = JSON.parse(inputText);
      artifact = {
        title: "SIMULATED content calendar",
        strategySummary: "Synthetic workflow acceptance only.",
        items: Array.from({ length: input.numberOfItems || 1 }, (_, i) => ({
          title: "Synthetic draft " + (i + 1),
          platform: input.platforms?.[0] || "instagram",
          contentType: "post",
          scheduledDate: input.startDate || "2026-10-28",
          copy: "Synthetic content for review before publication.",
          cta: "Review draft",
          hashtags: [],
        })),
      };
    }
  }
  res.writeHead(200, { "content-type": "application/json" });
  res.end(
    JSON.stringify({
      choices: [
        {
          message: {
            content: prompt.includes("SIMULATE_INVALID_ARTIFACT")
              ? "not valid JSON"
              : JSON.stringify(artifact),
          },
        },
      ],
      usage: { prompt_tokens: 100, completion_tokens: 100, total_tokens: 200 },
    }),
  );
});
server.listen(3102, "127.0.0.1", () =>
  console.log("Isolated marketing AI simulator on 127.0.0.1:3102"),
);
