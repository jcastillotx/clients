"use client";

import { useState } from "react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { AccountConnector } from "./account-connector";
import { PostScheduler } from "./post-scheduler";
import { PostsCalendar } from "./posts-calendar";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Facebook, Instagram, Twitter, Linkedin, Calendar, TrendingUp } from "lucide-react";

interface SocialAccount {
  id: string;
  platform: string;
  account_name: string;
  is_active: boolean;
  metadata?: {
    followerCount?: number;
    profileImage?: string;
  };
  created_at: string;
}

interface SocialPost {
  id: string;
  content: string;
  scheduled_for: string;
  published_at?: string;
  status: string;
  account: {
    platform: string;
    account_name: string;
  };
  engagement_metrics?: {
    likes?: number;
    comments?: number;
    shares?: number;
  };
}

interface SocialMediaDashboardProps {
  clientId: string;
  userId: string;
  initialAccounts: SocialAccount[];
  initialPosts: SocialPost[];
}

const platformIcons: Record<string, any> = {
  facebook: Facebook,
  instagram: Instagram,
  twitter: Twitter,
  linkedin: Linkedin,
};

export function SocialMediaDashboard({ clientId, userId, initialAccounts, initialPosts }: SocialMediaDashboardProps) {
  const [accounts, setAccounts] = useState<SocialAccount[]>(initialAccounts);
  const [posts, setPosts] = useState<SocialPost[]>(initialPosts);

  const handleAccountConnected = (newAccount: SocialAccount) => {
    setAccounts([...accounts, newAccount]);
  };

  const handlePostCreated = (newPost: SocialPost) => {
    setPosts([newPost, ...posts]);
  };

  const activeAccounts = accounts.filter((acc) => acc.is_active);
  const scheduledPosts = posts.filter((post) => post.status === "scheduled");
  const publishedPosts = posts.filter((post) => post.status === "published");

  const totalEngagement = publishedPosts.reduce((acc, post) => {
    const metrics = post.engagement_metrics || {};
    return acc + (metrics.likes || 0) + (metrics.comments || 0) + (metrics.shares || 0);
  }, 0);

  return (
    <div className="flex flex-col gap-6">
      {/* Overview Stats */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Connected Accounts</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{activeAccounts.length}</div>
            <p className="text-xs text-muted-foreground">{accounts.length} total accounts</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Scheduled Posts</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{scheduledPosts.length}</div>
            <p className="text-xs text-muted-foreground">Pending publication</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Published Posts</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{publishedPosts.length}</div>
            <p className="text-xs text-muted-foreground">Last 30 days</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Engagement</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalEngagement.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">Likes, comments, shares</p>
          </CardContent>
        </Card>
      </div>

      {/* Connected Accounts */}
      <Card>
        <CardHeader>
          <CardTitle>Connected Accounts</CardTitle>
          <CardDescription>Manage your social media account connections</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-4 mb-6">
            {accounts.map((account) => {
              const Icon = platformIcons[account.platform] || Facebook;
              return (
                <Card key={account.id} className="w-64">
                  <CardContent className="flex items-center gap-4 p-4">
                    <Icon className="h-8 w-8" />
                    <div className="flex-1">
                      <div className="font-semibold">{account.account_name}</div>
                      <div className="text-sm text-muted-foreground capitalize">{account.platform}</div>
                    </div>
                    <Badge variant={account.is_active ? "default" : "secondary"}>
                      {account.is_active ? "Active" : "Inactive"}
                    </Badge>
                  </CardContent>
                </Card>
              );
            })}
          </div>
          <AccountConnector clientId={clientId} onAccountConnected={handleAccountConnected} />
        </CardContent>
      </Card>

      {/* Main Content Tabs */}
      <Tabs defaultValue="scheduler" className="w-full">
        <TabsList>
          <TabsTrigger value="scheduler">Post Scheduler</TabsTrigger>
          <TabsTrigger value="calendar">Calendar View</TabsTrigger>
          <TabsTrigger value="posts">All Posts</TabsTrigger>
        </TabsList>

        <TabsContent value="scheduler">
          <PostScheduler
            clientId={clientId}
            userId={userId}
            accounts={activeAccounts}
            onPostCreated={handlePostCreated}
          />
        </TabsContent>

        <TabsContent value="calendar">
          <PostsCalendar posts={posts} />
        </TabsContent>

        <TabsContent value="posts">
          <Card>
            <CardHeader>
              <CardTitle>All Posts</CardTitle>
              <CardDescription>View and manage all your social media posts</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {posts.map((post) => {
                  const Icon = platformIcons[post.account?.platform] || Facebook;
                  return (
                    <Card key={post.id}>
                      <CardContent className="p-4">
                        <div className="flex items-start gap-4">
                          <Icon className="h-6 w-6 mt-1" />
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-2">
                              <span className="font-semibold">{post.account?.account_name}</span>
                              <Badge
                                variant={
                                  post.status === "published"
                                    ? "default"
                                    : post.status === "scheduled"
                                      ? "secondary"
                                      : "outline"
                                }
                              >
                                {post.status}
                              </Badge>
                              <span className="text-sm text-muted-foreground">
                                {new Date(post.scheduled_for).toLocaleString()}
                              </span>
                            </div>
                            <p className="text-sm mb-2">{post.content}</p>
                            {post.engagement_metrics && (
                              <div className="flex gap-4 text-sm text-muted-foreground">
                                <span>{post.engagement_metrics.likes || 0} likes</span>
                                <span>{post.engagement_metrics.comments || 0} comments</span>
                                <span>{post.engagement_metrics.shares || 0} shares</span>
                              </div>
                            )}
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
