import { PublicProjectRequestForm } from "@/components/projects/public-project-request-form";

export const metadata = {
  title: "Request a Project | KRE8IV",
  description: "Submit a project request for your organization",
};

export default function PublicProjectRequestPage() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-background via-secondary/30 to-background px-6 py-12">
      <div className="mx-auto max-w-5xl">
        <PublicProjectRequestForm />
      </div>
    </div>
  );
}
