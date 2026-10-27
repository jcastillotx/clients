import {describe, expect, it} from "vitest";
import {calendarDateSchema, countrySchema, emailSchema, phoneSchema, websiteSchema} from "@/lib/validations/fields";
import {clientFieldsSchema, updateClientFieldsSchema} from "@/lib/validations/client";
import {createPublicProjectRequestSchema} from "@/lib/validations/project-request";
import {createPublicInvoiceCheckoutSchema} from "@/lib/validations/public-invoice-payment";
import {createProjectSchema} from "@/lib/validations/project";
import {createTimeEntrySchema} from "@/lib/validations/time-entry";
import {createMeetingSchema} from "@/lib/validations/meeting";
import {createCampaignSchema, createLeadSchema, createContentSchema} from "@/lib/validations/marketing";
import {createRequestSchema} from "@/lib/validations/request";
import {createSupportTicketSchema} from "@/lib/validations/support-ticket";
import {createSurveySchema} from "@/lib/validations/survey";
import {createPartnerSchema, createReferralSchema, kbArticleSchema, staffGuideSchema} from "@/lib/validations/partners-kb";
import {contractFormSchema, taskFormSchema, maintenanceUsageSchema, messageFormSchema, userEditSchema} from "@/lib/validations/workspace";
import {validateFormRequest} from "@/lib/validations/form-request";
const id = "d88ff77a-1f00-4a4d-af16-550517ae7bf0";
const brief = {companyName:"Acme Corp",contactName:"Jane O’Connor",contactEmail:"jane@example.com",title:"New website",executiveSummary:"Build a new marketing website with clear service information."};
describe("shared form fields", () => {
  it.each(["+1 (210) 555-0123", "+44 20 7946 0958 ext 123", "555-0123", ""])("accepts valid optional phone %s", value => expect(phoneSchema.safeParse(value).success).toBe(true));
  it.each(["123", "not-a-phone", "111111111111111111", "+1 210 555 0123 ext nope"])("rejects invalid phone %s", value => expect(phoneSchema.safeParse(value).success).toBe(false));
  it.each(["example.com", "https://example.com/path?q=1", "https://例え.テスト"])("accepts web address %s", value => expect(websiteSchema.safeParse(value).success).toBe(true));
  it.each(["javascript:alert(1)", "ftp://example.com", "https://user:password@example.com", "not a website", "http://-bad.example.com"])("rejects unsafe or malformed web address %s", value => expect(websiteSchema.safeParse(value).success).toBe(false));
  it.each(["1970-05-31", "2028-02-29", "2030-01-01T12:00:00.000Z"])("accepts real calendar date %s", value => expect(calendarDateSchema.safeParse(value).success).toBe(true));
  it.each(["2026-02-30", "2025-02-29", "not-a-date", "03/04/2026"])("rejects invalid or ambiguous date %s", value => expect(calendarDateSchema.safeParse(value).success).toBe(false));
  it("normalizes surrounding email whitespace without changing its local part", () => expect(emailSchema.parse("  Jane@example.com  ")).toBe("Jane@example.com"));
  it.each(["US", "United States", "United Kingdom", "México", "Deutschland"])("recognizes country %s", value => expect(countrySchema.safeParse(value).success).toBe(true));
  it("rejects random country strings", () => expect(countrySchema.safeParse("Hrqgqnijq").success).toBe(false));
});
describe("public and business form contracts", () => {
  it.each([
    {companyName:"   "}, {contactEmail:"invalid"}, {contactPhone:"abc"}, {website:"javascript:alert(1)"},
    {executiveSummary:"                              "}, {requestedStartDate:"1970-05-31"}, {requestedLaunchDate:"2026-02-30"},
    {requestedStartDate:"2099-02-02",requestedLaunchDate:"2099-01-01"}, {country:"United States",zipCode:"ABC"},
  ])("rejects invalid public project fields before CAPTCHA/persistence: %j", patch => expect(createPublicProjectRequestSchema.safeParse({...brief,...patch}).success).toBe(false));
  it("accepts legitimate international names, websites, phone and future dates", () => expect(createPublicProjectRequestSchema.safeParse({...brief,contactName:"李 明",contactPhone:"+44 20 7946 0958",website:"example.com",country:"GB",requestedStartDate:"2099-01-01",requestedLaunchDate:"2099-02-01"}).success).toBe(true));
  it.each([NaN,Infinity,-1,0,1.001])("rejects invalid payment amount %s", paymentAmount => expect(createPublicInvoiceCheckoutSchema.safeParse({invoiceNumber:"INV-1",paymentAmount,email:"jane@example.com",businessName:"Acme",contactName:"Jane"}).success).toBe(false));
  it("validates client edits as strictly as creates", () => {
    expect(clientFieldsSchema.safeParse({company_name:"  ",email:"jane@example.com"}).success).toBe(false);
    expect(updateClientFieldsSchema.safeParse({email:"invalid"}).success).toBe(false);
    expect(updateClientFieldsSchema.safeParse({phone:"123"}).success).toBe(false);
  });
  it("rejects reversed project and tracked-time periods", () => {
    expect(createProjectSchema.safeParse({clientId:id,name:"Build",startDate:"2099-02-01",endDate:"2099-01-01"}).success).toBe(false);
    expect(createTimeEntrySchema.safeParse({startedAt:"2030-01-01T12:00:00Z",endedAt:"2030-01-01T11:00:00Z"}).success).toBe(false);
  });
  it("rejects duration values that disagree with tracked start/end times", () => {
    expect(createTimeEntrySchema.safeParse({startedAt:"2030-01-01T12:00:00Z",endedAt:"2030-01-01T13:00:00Z",durationMinutes:5}).success).toBe(false);
    expect(createTimeEntrySchema.safeParse({startedAt:"2030-01-01T12:00:00Z",endedAt:"2030-01-01T13:00:00Z",durationMinutes:60,hourlyRate:0}).success).toBe(true);
  });
  it("accepts the project UI's date-only values and empty manager selection", () => expect(createProjectSchema.safeParse({clientId:id,name:"Build",startDate:"2030-01-01",endDate:"2030-02-01",projectManagerId:"",budgets:[{category:"design",allocatedAmount:"2500",currency:"USD"}],milestones:[{title:"Launch",dueDate:"2030-02-01"}]}).success).toBe(true));
  it("validates contract and maintenance inputs", () => {
    expect(contractFormSchema.safeParse({title:"Build",clientId:id,type:"service_agreement",startDate:"2030-02-01",endDate:"2030-01-01"}).success).toBe(false);
    expect(maintenanceUsageSchema.safeParse({hoursUsed:-1,description:"Build"}).success).toBe(false);
    expect(maintenanceUsageSchema.safeParse({hoursUsed:"abc",description:"Build"}).success).toBe(false);
  });
  it("does not alter passwords or require a new password on user edits", () => {
    expect(userEditSchema.parse({password:"  secret123  "}).password).toBe("  secret123  ");
    expect(userEditSchema.safeParse({name:"Jane",password:""}).success).toBe(true);
  });
  it.each([
    [createRequestSchema,{title:"   "}], [createSupportTicketSchema,{subject:"  ",description:"Meaningful detail",category:"technical"}],
    [createMeetingSchema,{clientId:id,title:" ",meetingType:"review",scheduledAt:"2030-01-01T12:00:00Z"}],
    [createSurveySchema,{title:" ",questions:[]}], [createCampaignSchema,{name:" ",type:"email"}],
    [createLeadSchema,{name:"  ",email:"jane@example.com",source:"website"}], [createContentSchema,{title:" ",content:"Hello",content_type:"post",platform:"facebook"}],
    [createPartnerSchema,{companyName:" ",contactName:"Jane",email:"jane@example.com"}], [createReferralSchema,{partnerId:id,referredName:" "}],
    [kbArticleSchema,{categoryId:id,title:" ",content:"Hello"}], [staffGuideSchema,{categoryId:id,title:" ",content:"Hello"}],
    [taskFormSchema,{boardId:id,columnId:id,title:" "}], [messageFormSchema,{conversationId:id,messageBody:" "}],
  ] as const)("rejects whitespace-only required fields across business forms", (schema,payload) => expect(schema.safeParse(payload).success).toBe(false));
  it("runs the API contract in the browser preflight, without catching unrelated actions", () => {
    expect(validateFormRequest("/api/public/project-requests","POST",{...brief,contactPhone:"123"})?.success).toBe(false);
    expect(validateFormRequest("/api/clients/"+id,"PATCH",{email:"bad"})?.success).toBe(false);
    expect(validateFormRequest("/api/clients/"+id+"/archive","POST",{})).toBeUndefined();
  });
});
