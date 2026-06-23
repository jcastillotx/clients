import { KbArticleDetail } from "@/components/knowledge-base/kb-article-detail";

export const metadata = {
  title: "Article | Knowledge Base",
  description: "Knowledge base article",
};

type PageProps = {
  params: Promise<{ id: string }>;
};

export default async function KbArticlePage({ params }: PageProps) {
  const { id } = await params;

  return (
    <div className="container mx-auto max-w-4xl py-6">
      <KbArticleDetail articleId={id} />
    </div>
  );
}
