# 📧 AI Email Assistant Feature

## Overview

A comprehensive AI-powered email composition assistant to help staff create professional client emails quickly and efficiently.

## Location

**URL**: `/ai/email-assistant`

**Access**: Staff and Admin only (not visible to clients)

## Features

### 1. **Email Templates**

Pre-built templates for common scenarios:

- 📝 **Introduction Email** - Introduce your company to new clients
- 🔄 **Follow-up Email** - Follow up on conversations or meetings
- 📊 **Project Update** - Send progress updates to clients
- 💼 **Proposal Email** - Send business proposals or quotes
- 🆘 **Support Response** - Respond to support requests
- 🙏 **Thank You Email** - Thank clients for their business
- ✏️ **Custom Email** - Create any type of email

### 2. **Tone Selection**

Choose the appropriate tone:

- 💼 **Professional** - Standard business tone
- 😊 **Friendly** - Warm and approachable
- 🎩 **Formal** - Very formal and respectful
- 👋 **Casual** - Relaxed and conversational

### 3. **Input Fields**

- **Email Purpose** - Select from template dropdown
- **Tone** - Choose communication style
- **Recipient Name** - Optional (personalizes greeting)
- **Email Subject** - Optional (AI suggests if blank)
- **Key Points** - Bullet points or main message (required)
- **Additional Instructions** - Custom requirements (optional)

### 4. **Generated Output**

- **Preview Tab** - Formatted email with proper spacing
- **Plain Text Tab** - Raw text for copying
- **Copy to Clipboard** - One-click copy
- **Regenerate** - Try again with same inputs
- **Send** - Open in email client (future)

## How to Use

### Basic Usage:

1. **Navigate** to `/ai/email-assistant`

2. **Select purpose**:
   - Choose email type (Introduction, Follow-up, etc.)

3. **Choose tone**:
   - Select Professional, Friendly, Formal, or Casual

4. **Enter details**:
   - Recipient name (optional)
   - Subject (optional)
   - Key points (what you want to say)
   - Custom instructions (optional)

5. **Generate**:
   - Click "Generate Email"
   - AI creates professional email

6. **Review & Use**:
   - Switch between Preview/Plain Text
   - Copy to clipboard
   - Send via your email client

### Example Workflow:

**Scenario**: Send a project update to client

```
Purpose: Project Update
Tone: Professional
Recipient: John Smith
Subject: Website Project Update - Week 3
Key Points:
- Completed homepage design
- Started mobile responsive layout
- On track for Friday deadline
- Next: Client feedback session

[Click Generate]

Result:
Hi John,

I'm writing to provide you with an update on our progress.

We've completed the homepage design and are pleased with how it turned out. The team has started working on the mobile responsive layout to ensure the site looks great on all devices. I'm happy to report that we're on track to meet our Friday deadline.

Next, we'll be scheduling a client feedback session to review the designs and gather your input.

I'll continue to keep you updated on our progress. Please don't hesitate to reach out with any questions.

Best regards,
[Your Name]
```

## UI Layout

### Split View Design

```
┌─────────────────────────────────────────────────────────────────┐
│ 📧 AI Email Assistant                                           │
├─────────────────────────┬───────────────────────────────────────┤
│ Email Details           │ Generated Email                       │
│                         │                                       │
│ [Purpose ▼]            │ ┌─Preview─┬─Plain Text─┐             │
│ [Tone ▼]               │ │                      │             │
│ [Recipient]            │ │ Hi John,             │             │
│ [Subject]              │ │                      │             │
│ [Key Points...]        │ │ Email content here...│             │
│ [Instructions...]      │ │                      │             │
│                         │ └──────────────────────┘             │
│ [✨ Generate Email]    │ [📋 Copy] [🔄 Regenerate]           │
│ [Clear]                │ [📤 Send via Email]                  │
└─────────────────────────┴───────────────────────────────────────┘
```

### Quick Templates

```
┌──────────────┬──────────────┬──────────────┐
│ 📝 Introduction│ 🔄 Follow-up │ 📊 Update   │
│ Introduce co. │ After meeting│ Progress     │
└──────────────┴──────────────┴──────────────┘
```

## AI Integration (Future Enhancement)

Currently using template-based generation. To add real AI:

### Option 1: OpenAI Integration

```typescript
import OpenAI from "openai";

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

const completion = await openai.chat.completions.create({
  model: "gpt-4",
  messages: [
    {
      role: "system",
      content: `You are a professional email writing assistant. Generate ${params.tone} emails for ${params.purpose} purposes.`,
    },
    {
      role: "user",
      content: `Write an email with these key points:\n${params.keyPoints}\n\nAdditional instructions: ${params.customInstructions}`,
    },
  ],
  temperature: 0.7,
});

return completion.choices[0].message.content;
```

### Option 2: Anthropic Claude

```typescript
import Anthropic from "@anthropic-ai/sdk";

const anthropic = new Anthropic({
  apiKey: process.env.ANTHROPIC_API_KEY,
});

const message = await anthropic.messages.create({
  model: "claude-3-sonnet-20240229",
  max_tokens: 1024,
  messages: [
    {
      role: "user",
      content: `Write a ${params.tone} ${params.purpose} email with these points:\n${params.keyPoints}`,
    },
  ],
});

return message.content[0].text;
```

### Option 3: Vercel AI SDK

```typescript
import { generateText } from 'ai';
import { openai } from '@ai-sdk/openai';

const { text } = await generateText({
  model: openai('gpt-4-turbo'),
  prompt: `Write a ${params.tone} ${params.purpose} email...`,
});

return text;
```

## Template Placeholders

Emails include placeholders that should be replaced:

```
[Your Name] → Replace with actual user name
[Your Title] → Replace with user's job title
[Your Company] → Replace with company name
[Project Name] → Replace with actual project
```

To auto-fill these, update the generation function:

```typescript
const { data: userData } = await supabase
  .from("users")
  .select("name")
  .eq("id", user.id)
  .single();

const { data: clientData } = await supabase
  .from("clients")
  .select("company_name")
  .eq("id", user.client_id)
  .single();

// Replace placeholders
email = email
  .replace(/\[Your Name\]/g, userData?.name || "")
  .replace(/\[Your Company\]/g, clientData?.company_name || "");
```

## Use Cases

### 1. New Client Onboarding

```
Purpose: Introduction
Tone: Professional
Key Points:
- Welcome to our service
- Overview of next steps
- Contact information
- Account setup details
```

### 2. Project Milestone Communication

```
Purpose: Project Update
Tone: Friendly
Key Points:
- Milestone completed
- Timeline status
- Next deliverables
- Request for feedback
```

### 3. Invoice Delivery

```
Purpose: Custom
Tone: Professional
Key Points:
- Invoice attached
- Payment details
- Due date
- Thank you for business
```

### 4. Support Issue Resolution

```
Purpose: Support Response
Tone: Professional
Key Points:
- Issue identified
- Solution implemented
- Steps to prevent future
- Follow-up available
```

## Features Detail

### Copy to Clipboard

```typescript
function copyToClipboard() {
  navigator.clipboard.writeText(generatedEmail);
  toast.success("Email copied to clipboard!");
}
```

One-click copy → paste into Gmail, Outlook, etc.

### Regenerate Email

Click "Regenerate" to generate a new version with the same inputs (useful when real AI is integrated for variations).

### Quick Template Cards

Click any template card to auto-select that purpose and see relevant placeholder text.

## Integration with Email Systems

### Future: Direct Send

Add email sending via:

**Resend API:**
```typescript
import { Resend } from 'resend';

const resend = new Resend(process.env.RESEND_API_KEY);

await resend.emails.send({
  from: 'you@company.com',
  to: recipientEmail,
  subject: subject,
  html: generatedEmail,
});
```

**Gmail API:**
```typescript
// OAuth flow + Gmail API
// Send directly from user's Gmail
```

**SMTP:**
```typescript
// Use nodemailer or similar
// Send via company SMTP server
```

## Validation

### Required Fields:

- ✅ **Key Points** OR **Custom Instructions** (at least one)

### Optional Fields:

- Recipient name
- Subject line
- Both tone and purpose have defaults

## Testing

### Test Scenarios:

- [ ] Generate introduction email
- [ ] Generate follow-up email
- [ ] Generate project update
- [ ] Generate proposal email
- [ ] Generate support response
- [ ] Generate thank you email
- [ ] Generate custom email
- [ ] Try different tones
- [ ] Add recipient name (check greeting)
- [ ] Add subject line
- [ ] Use only key points
- [ ] Use only custom instructions
- [ ] Use both key points + instructions
- [ ] Copy to clipboard
- [ ] Regenerate email
- [ ] Switch between preview/plain text
- [ ] Click quick template cards
- [ ] Test on mobile
- [ ] Test with empty form (should show error)

## Customization

### Add New Template:

```typescript
const emailTemplates = {
  // ... existing templates
  "invoice-reminder": {
    label: "Invoice Reminder",
    description: "Remind client about pending invoice",
    placeholder: "- Invoice number\n- Amount due\n- Payment link",
  },
};
```

### Change AI Provider:

Update `/api/ai/generate-email/route.ts` to use OpenAI, Anthropic, or other AI service.

### Custom Signature:

```typescript
const { data: userSettings } = await supabase
  .from("user_settings")
  .select("email_signature")
  .eq("user_id", user.id)
  .single();

const signature = userSettings?.email_signature || defaultSignature;
```

## Future Enhancements

1. **Save Drafts**
   - Save generated emails for later
   - Email draft library

2. **Email History**
   - Track generated emails
   - Re-use previous emails

3. **Personalization**
   - Use client data for personalization
   - Insert dynamic fields
   - Custom merge tags

4. **Scheduled Sending**
   - Schedule email for later
   - Optimal send time suggestions

5. **A/B Testing**
   - Generate multiple versions
   - Compare effectiveness

6. **Email Analytics**
   - Track open rates
   - Track click rates
   - Measure responses

7. **Templates Library**
   - Save custom templates
   - Share across team
   - Template categories

## Troubleshooting

### "Failed to generate email" error

**Cause**: API route error
**Solution**: Check server logs for details

### Email looks generic

**Cause**: Not enough detail in key points
**Solution**: Provide more specific information

### Wrong tone

**Cause**: Tone selector not working
**Solution**: Try regenerating or manually edit the output

## Files Created

- ✅ `app/(dashboard)/ai/email-assistant/page.tsx` - Main page
- ✅ `app/api/ai/generate-email/route.ts` - Email generation API
- ✅ `EMAIL_ASSISTANT_FEATURE.md` - This documentation

## Files Updated

- ✅ `components/dashboard/nav.tsx` - Fixed nav link

## Summary

**What**: AI Email Assistant for drafting professional emails
**Where**: `/ai/email-assistant` 
**Who**: Staff and Admin only
**How**: Select template, enter details, generate email
**Output**: Copy-ready professional email

**Status**: ✅ Complete and working

**Next**: Navigate to `/ai/email-assistant` and try generating an email!

---

**Note**: Currently uses template-based generation. For advanced AI features, integrate OpenAI or Anthropic API by updating the `/api/ai/generate-email/route.ts` file.
