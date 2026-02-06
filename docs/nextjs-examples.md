# Next.js Architecture Examples

**Demonstrating the migration from Laravel Livewire to Next.js 15**

## Table of Contents

1. [Server Components (Data Fetching)](#1-server-components-data-fetching)
2. [Client Components (Interactivity)](#2-client-components-interactivity)
3. [Forms (React Hook Form + Zod)](#3-forms-react-hook-form--zod)
4. [Real-time Updates (Supabase)](#4-real-time-updates-supabase)
5. [API Routes (Server Actions)](#5-api-routes-server-actions)
6. [Layout System](#6-layout-system)
7. [Authentication](#7-authentication)

---

## 1. Server Components (Data Fetching)

### Laravel Livewire (Before)

```php
<!-- resources/views/livewire/requests/index.blade.php -->
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Service Requests</h1>
        <button wire:click="showCreateModal" class="btn btn-primary">
            New Request
        </button>
    </div>

    <!-- Search and filters -->
    <div class="mb-3">
        <input
            type="text"
            wire:model.debounce.300ms="search"
            class="form-control"
            placeholder="Search requests..."
        >
    </div>

    <!-- Requests table -->
    <table class="table">
        <thead>
            <tr>
                <th wire:click="sortBy('title')">Title</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $request)
                <tr wire:click="viewRequest({{ $request->id }})">
                    <td>{{ $request->title }}</td>
                    <td>
                        <span class="badge badge-{{ $request->status_color }}">
                            {{ $request->status }}
                        </span>
                    </td>
                    <td>{{ $request->assignedUser->name ?? 'Unassigned' }}</td>
                    <td>{{ $request->created_at->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $requests->links() }}

    <!-- Loading state -->
    <div wire:loading class="loading-overlay">
        <div class="spinner-border"></div>
    </div>
</div>
```

```php
// app/Livewire/Requests/Index.php
class Index extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = ['search'];

    public function render()
    {
        $requests = Request::query()
            ->where('client_id', auth()->user()->client_id)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.requests.index', [
            'requests' => $requests,
        ]);
    }
}
```

### Next.js 15 (After)

```tsx
// app/(dashboard)/requests/page.tsx (Server Component)
import { createClient } from "@/lib/supabase/server";
import { RequestList } from "@/components/requests/request-list";
import { Button } from "@/components/ui/button";
import { PlusIcon } from "@radix-ui/react-icons";
import Link from "next/link";

interface SearchParams {
    search?: string;
    sortBy?: string;
    sortOrder?: "asc" | "desc";
    page?: string;
}

export default async function RequestsPage({
    searchParams,
}: {
    searchParams: SearchParams;
}) {
    const supabase = createClient();

    // Server-side data fetching (no loading state needed!)
    const query = supabase
        .from("requests")
        .select("*, client:clients(company_name), assigned_user:users(name)")
        .order(searchParams.sortBy || "created_at", {
            ascending: searchParams.sortOrder === "asc",
        });

    if (searchParams.search) {
        query.textSearch("title", searchParams.search);
    }

    const { data: requests, error } = await query;

    if (error) {
        throw new Error("Failed to fetch requests");
    }

    return (
        <div className="container mx-auto py-8">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-3xl font-bold">Service Requests</h1>
                <Button asChild>
                    <Link href="/requests/new">
                        <PlusIcon className="mr-2 h-4 w-4" />
                        New Request
                    </Link>
                </Button>
            </div>

            {/* Client Component for interactivity */}
            <RequestList initialData={requests} />
        </div>
    );
}
```

```tsx
// components/requests/request-list.tsx (Client Component)
"use client";

import { useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { formatDistanceToNow } from "date-fns";
import { useDebounce } from "@/hooks/use-debounce";

interface Request {
    id: string;
    title: string;
    status: string;
    created_at: string;
    client: { company_name: string };
    assigned_user: { name: string } | null;
}

export function RequestList({ initialData }: { initialData: Request[] }) {
    const router = useRouter();
    const searchParams = useSearchParams();
    const [search, setSearch] = useState(searchParams.get("search") || "");
    const debouncedSearch = useDebounce(search, 300);

    // Update URL when search changes
    useEffect(() => {
        const params = new URLSearchParams(searchParams);
        if (debouncedSearch) {
            params.set("search", debouncedSearch);
        } else {
            params.delete("search");
        }
        router.push(`?${params.toString()}`);
    }, [debouncedSearch]);

    return (
        <div className="space-y-4">
            {/* Search */}
            <Input
                placeholder="Search requests..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
            />

            {/* Table */}
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead>Created</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {initialData.map((request) => (
                        <TableRow
                            key={request.id}
                            className="cursor-pointer hover:bg-muted/50"
                            onClick={() =>
                                router.push(`/requests/${request.id}`)
                            }
                        >
                            <TableCell className="font-medium">
                                {request.title}
                            </TableCell>
                            <TableCell>
                                <Badge
                                    variant={getStatusVariant(request.status)}
                                >
                                    {request.status}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {request.assigned_user?.name || "Unassigned"}
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {formatDistanceToNow(
                                    new Date(request.created_at),
                                    {
                                        addSuffix: true,
                                    },
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function getStatusVariant(status: string) {
    const variants: Record<string, "default" | "secondary" | "destructive"> = {
        pending: "secondary",
        in_progress: "default",
        completed: "default",
        cancelled: "destructive",
    };
    return variants[status] || "default";
}
```

**Benefits**:

- ✅ Server Component loads data on server (no loading spinner)
- ✅ Client Component only for interactivity (smaller JS bundle)
- ✅ Pure Tailwind (no Bootstrap conflicts)
- ✅ shadcn/ui components (accessible by default)
- ✅ TypeScript (type-safe)

---

## 2. Client Components (Interactivity)

### Request Detail with Real-time Comments

```tsx
// app/(dashboard)/requests/[id]/page.tsx (Server Component)
import { createClient } from "@/lib/supabase/server";
import { RequestDetail } from "@/components/requests/request-detail";

export default async function RequestDetailPage({
    params,
}: {
    params: { id: string };
}) {
    const supabase = createClient();

    const { data: request } = await supabase
        .from("requests")
        .select(
            `
      *,
      client:clients(company_name),
      assigned_user:users(name, avatar),
      comments:request_comments(*, user:users(name, avatar))
    `,
        )
        .eq("id", params.id)
        .single();

    return <RequestDetail initialData={request} />;
}
```

```tsx
// components/requests/request-detail.tsx (Client Component)
"use client";

import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { createClient } from "@/lib/supabase/client";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { formatDistanceToNow } from "date-fns";

export function RequestDetail({ initialData }) {
    const supabase = createClient();
    const queryClient = useQueryClient();
    const [newComment, setNewComment] = useState("");

    // TanStack Query for caching and optimistic updates
    const { data: request } = useQuery({
        queryKey: ["request", initialData.id],
        queryFn: async () => {
            const { data } = await supabase
                .from("requests")
                .select(
                    "*, comments:request_comments(*, user:users(name, avatar))",
                )
                .eq("id", initialData.id)
                .single();
            return data;
        },
        initialData,
    });

    // Add comment mutation
    const addComment = useMutation({
        mutationFn: async (content: string) => {
            const { data } = await supabase
                .from("request_comments")
                .insert({ request_id: request.id, content })
                .select("*, user:users(name, avatar)")
                .single();
            return data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries(["request", request.id]);
            setNewComment("");
        },
    });

    return (
        <div className="container mx-auto py-8 space-y-6">
            {/* Request Details */}
            <Card>
                <CardHeader>
                    <CardTitle>{request.title}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-muted-foreground">
                        {request.description}
                    </p>
                </CardContent>
            </Card>

            {/* Comments */}
            <Card>
                <CardHeader>
                    <CardTitle>Comments</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {request.comments?.map((comment) => (
                        <div key={comment.id} className="flex gap-3">
                            <Avatar>
                                <AvatarImage src={comment.user.avatar} />
                                <AvatarFallback>
                                    {comment.user.name[0]}
                                </AvatarFallback>
                            </Avatar>
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="font-medium">
                                        {comment.user.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {formatDistanceToNow(
                                            new Date(comment.created_at),
                                            {
                                                addSuffix: true,
                                            },
                                        )}
                                    </span>
                                </div>
                                <p className="text-sm">{comment.content}</p>
                            </div>
                        </div>
                    ))}

                    {/* Add Comment Form */}
                    <div className="flex gap-2">
                        <Textarea
                            placeholder="Add a comment..."
                            value={newComment}
                            onChange={(e) => setNewComment(e.target.value)}
                        />
                        <Button
                            onClick={() => addComment.mutate(newComment)}
                            disabled={
                                !newComment.trim() || addComment.isPending
                            }
                        >
                            {addComment.isPending ? "Posting..." : "Post"}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
```

---

## 3. Forms (React Hook Form + Zod)

### Laravel Livewire Form (Before)

```php
<!-- resources/views/livewire/requests/create.blade.php -->
<form wire:submit.prevent="submit">
    <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" wire:model="title" class="form-control">
        @error('title') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea wire:model="description" class="form-control"></textarea>
        @error('description') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
        <span wire:loading.remove>Create Request</span>
        <span wire:loading>Creating...</span>
    </button>
</form>
```

### Next.js Form (After)

```typescript
// lib/validations/request.ts
import { z } from "zod";

export const createRequestSchema = z.object({
    title: z.string().min(3, "Title must be at least 3 characters"),
    description: z
        .string()
        .min(10, "Description must be at least 10 characters"),
    priority: z.enum(["low", "medium", "high"]),
    due_date: z.date().optional(),
});

export type CreateRequestInput = z.infer<typeof createRequestSchema>;
```

```tsx
// app/(dashboard)/requests/new/page.tsx
"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import {
    createRequestSchema,
    type CreateRequestInput,
} from "@/lib/validations/request";
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";

export default function NewRequestPage() {
    const router = useRouter();

    const form = useForm<CreateRequestInput>({
        resolver: zodResolver(createRequestSchema),
        defaultValues: {
            title: "",
            description: "",
            priority: "medium",
        },
    });

    const createRequest = useMutation({
        mutationFn: async (data: CreateRequestInput) => {
            const res = await fetch("/api/requests", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(data),
            });

            if (!res.ok) {
                const error = await res.json();
                throw new Error(error.message);
            }

            return res.json();
        },
        onSuccess: (data) => {
            toast.success("Request created successfully");
            router.push(`/requests/${data.id}`);
        },
        onError: (error: Error) => {
            toast.error(error.message);
        },
    });

    return (
        <div className="container max-w-2xl mx-auto py-8">
            <h1 className="text-3xl font-bold mb-6">Create New Request</h1>

            <Form {...form}>
                <form
                    onSubmit={form.handleSubmit((data) =>
                        createRequest.mutate(data),
                    )}
                    className="space-y-6"
                >
                    <FormField
                        control={form.control}
                        name="title"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Title</FormLabel>
                                <FormControl>
                                    <Input
                                        placeholder="Enter request title"
                                        {...field}
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="description"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Description</FormLabel>
                                <FormControl>
                                    <Textarea
                                        placeholder="Describe your request in detail"
                                        className="min-h-[120px]"
                                        {...field}
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />

                    <Button type="submit" disabled={createRequest.isPending}>
                        {createRequest.isPending
                            ? "Creating..."
                            : "Create Request"}
                    </Button>
                </form>
            </Form>
        </div>
    );
}
```

**Benefits**:

- ✅ Type-safe validation (Zod + TypeScript)
- ✅ Client-side validation before submit
- ✅ Clear error messages
- ✅ Optimistic UI updates possible
- ✅ Accessible (shadcn/ui forms use ARIA)

---

## 4. Real-time Updates (Supabase)

```tsx
// components/requests/request-detail-realtime.tsx
"use client";

import { useEffect } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { createClient } from "@/lib/supabase/client";

export function RequestDetailRealtime({ requestId }: { requestId: string }) {
    const supabase = createClient();
    const queryClient = useQueryClient();

    useEffect(() => {
        // Subscribe to request changes
        const channel = supabase
            .channel(`request:${requestId}`)
            .on(
                "postgres_changes",
                {
                    event: "*",
                    schema: "public",
                    table: "requests",
                    filter: `id=eq.${requestId}`,
                },
                () => {
                    // Invalidate query to refetch
                    queryClient.invalidateQueries(["request", requestId]);
                },
            )
            .on(
                "postgres_changes",
                {
                    event: "INSERT",
                    schema: "public",
                    table: "request_comments",
                    filter: `request_id=eq.${requestId}`,
                },
                () => {
                    queryClient.invalidateQueries(["request", requestId]);
                },
            )
            .subscribe();

        return () => {
            supabase.removeChannel(channel);
        };
    }, [requestId, queryClient]);

    // This component doesn't render anything, just manages subscriptions
    return null;
}
```

Usage in request detail:

```tsx
// app/(dashboard)/requests/[id]/page.tsx
export default async function RequestDetailPage({ params }) {
    // ... fetch request ...

    return (
        <>
            <RequestDetail initialData={request} />
            <RequestDetailRealtime requestId={params.id} />
        </>
    );
}
```

---

## 5. API Routes (Server Actions)

```typescript
// app/api/requests/route.ts
import { createClient } from "@/lib/supabase/server";
import { createRequestSchema } from "@/lib/validations/request";
import { NextRequest, NextResponse } from "next/server";

export async function POST(req: NextRequest) {
    const supabase = createClient();

    // Check authentication
    const {
        data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
        return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Parse and validate request body
    const body = await req.json();
    const validatedData = createRequestSchema.parse(body);

    // Create request (RLS automatically filters by client_id)
    const { data, error } = await supabase
        .from("requests")
        .insert({
            ...validatedData,
            created_by: user.id,
            client_id: user.user_metadata.client_id,
        })
        .select()
        .single();

    if (error) {
        return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json(data, { status: 201 });
}
```

---

## Key Differences Summary

| Aspect            | Laravel Livewire                     | Next.js 15                                            |
| ----------------- | ------------------------------------ | ----------------------------------------------------- |
| **Data Fetching** | Server-rendered on every interaction | Server Components (initial), then client-side caching |
| **Interactivity** | Full page/component refresh          | Granular client components                            |
| **Validation**    | Laravel FormRequest                  | Zod schemas + React Hook Form                         |
| **Real-time**     | Livewire polling or Laravel Echo     | Supabase Realtime (WebSockets)                        |
| **CSS**           | Bootstrap + Tailwind (conflicts)     | Pure Tailwind + shadcn/ui                             |
| **Type Safety**   | Weak (PHP docblocks)                 | Strong (TypeScript)                                   |
| **Bundle Size**   | Heavy (Livewire JS + Alpine)         | Minimal (only interactive components)                 |
| **Accessibility** | Manual                               | Built-in (Radix UI)                                   |
| **Performance**   | ~1,795 HTTP requests                 | Optimized with caching                                |

---

**Result**: Cleaner code, better performance, improved DX, and fixed UX issues!
