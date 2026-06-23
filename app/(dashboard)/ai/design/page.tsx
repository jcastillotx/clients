import { Metadata } from "next";
import { StitchStudio } from "@/components/ai/stitch-studio";

export const metadata: Metadata = {
  title: "Design Studio | Google Stitch",
  description: "Generate UI screens from text prompts with Google Stitch",
};

export default function StitchDesignPage() {
  return (
    <div className="container mx-auto p-6 space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Design Studio</h1>
        <p className="text-muted-foreground mt-2">
          Generate and iterate on UI screens from natural language prompts using Google Stitch.
        </p>
      </div>
      <StitchStudio />
    </div>
  );
}
