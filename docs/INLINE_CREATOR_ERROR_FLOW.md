# Inline Creator Error Handling Flow

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    User Clicks "Add New"                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Modal Opens                                │
│  • Form fields displayed                                        │
│  • Focus trapped in modal                                       │
│  • Background dimmed                                            │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                   User Fills Form                               │
│  • Real-time validation as user types                           │
│  • Character count validation                                   │
│  • Errors clear when corrected                                  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  User Clicks "Create"                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Client-Side Validation                             │
│  • Check required fields                                        │
│  • Validate field lengths                                       │
│  • Show inline errors if invalid                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
              Valid │                 │ Invalid
                    ▼                 ▼
    ┌───────────────────────┐  ┌──────────────────────┐
    │  Set Loading State    │  │  Show Error Messages │
    │  • Disable button     │  │  • Red borders       │
    │  • Show spinner       │  │  • Error text        │
    │  • Disable inputs     │  │  • Focus first error │
    │  • "Creating..."      │  │  • Keep modal open   │
    └──────────┬────────────┘  └──────────────────────┘
               │                         │
               ▼                         │
    ┌───────────────────────┐           │
    │   Send AJAX Request   │           │
    │   • POST to endpoint  │           │
    │   • Include CSRF      │           │
    │   • 30s timeout       │           │
    └──────────┬────────────┘           │
               │                         │
      ┌────────┴────────┐               │
      │                 │               │
Success│                 │Error          │
      ▼                 ▼               │
┌──────────────┐  ┌─────────────────┐  │
│   Success    │  │  Error Response │  │
│   Response   │  │  • Network      │  │
│              │  │  • Validation   │  │
│              │  │  • Server       │  │
└──────┬───────┘  └────────┬────────┘  │
       │                   │            │
       │          ┌────────┴────────┐   │
       │          │                 │   │
       │    Network│           Other│   │
       │      Error│           Error│   │
       │          ▼                 ▼   │
       │  ┌──────────────┐  ┌──────────────────┐
       │  │ Retry Logic  │  │  Handle Error    │
       │  │ • Attempt 1  │  │  • Parse status  │
       │  │ • Wait 1s    │  │  • Show message  │
       │  │ • Attempt 2  │  │  • Keep modal    │
       │  │ • Wait 2s    │  │  • Enable retry  │
       │  │ • Max 2      │  └────────┬─────────┘
       │  └──────┬───────┘           │
       │         │                   │
       │         │ Still             │
       │         │ Fails             │
       │         ▼                   │
       │  ┌──────────────┐           │
       │  │ Show Network │           │
       │  │ Error Message│           │
       │  └──────┬───────┘           │
       │         │                   │
       │         └───────────────────┘
       │                   │
       ▼                   ▼
┌──────────────────────────────────────┐
│      Reset Loading State             │
│      • Enable button                 │
│      • Hide spinner                  │
│      • Enable inputs                 │
│      • "Create" text                 │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│         Display Feedback             │
│                                      │
│  SUCCESS:                            │
│  • Green success banner              │
│  • Update dropdown                   │
│  • Highlight dropdown (green ring)   │
│  • Show toast notification           │
│  • Auto-close after 1.5s             │
│                                      │
│  ERROR:                              │
│  • Red error banner                  │
│  • Field-specific errors             │
│  • Red borders on fields             │
│  • Keep modal open                   │
│  • Allow retry                       │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│      Announce to Screen Reader       │
│      • ARIA live region              │
│      • Success: polite               │
│      • Error: assertive              │
└──────────────────────────────────────┘
```

## Error Type Decision Tree

```
Error Received
    │
    ├─ Has response.status?
    │   │
    │   ├─ 400 → "Invalid request. Please check your input and try again."
    │   ├─ 401 → "Your session has expired. Please refresh the page and log in again."
    │   ├─ 403 → "You do not have permission to perform this action."
    │   ├─ 404 → "The requested resource was not found. Please refresh the page and try again."
    │   ├─ 419 → "Your session has expired. Please refresh the page and try again."
    │   ├─ 422 → Parse validation errors
    │   │         │
    │   │         ├─ Has 'name' error with 'already'/'taken'/'exists'?
    │   │         │   └─ "A [type] with this name already exists. Please choose a different name."
    │   │         │
    │   │         └─ Show field-specific errors + summary
    │   │
    │   ├─ 429 → "Too many requests. Please wait a moment and try again."
    │   ├─ 500+ → "A server error occurred. Please try again later or contact support if the problem persists."
    │   └─ Other → Use response.data.message or generic error
    │
    ├─ Has request but no response?
    │   │
    │   └─ Network Error
    │       │
    │       ├─ Retry count < 2?
    │       │   └─ Retry with exponential backoff
    │       │
    │       └─ "Network error. Please check your internet connection and try again."
    │
    ├─ Error code = 'ECONNABORTED'?
    │   └─ "Request timed out. Please check your connection and try again."
    │
    └─ Other
        └─ "An unexpected error occurred. Please try again."
```

## Loading State Transitions

```
Initial State
    │
    │ User clicks "Create"
    ▼
┌─────────────────────────────────────┐
│ LOADING STATE ENABLED               │
│ • submitButton.disabled = true      │
│ • submitButton.aria-busy = true     │
│ • submitButton.opacity = 75%        │
│ • submitText = "Creating..."        │
│ • spinner.hidden = false            │
│ • cancelButton.disabled = true      │
│ • All inputs.disabled = true        │
└─────────────────┬───────────────────┘
                  │
                  │ Request completes (success or error)
                  ▼
┌─────────────────────────────────────┐
│ LOADING STATE DISABLED              │
│ • submitButton.disabled = false     │
│ • submitButton.aria-busy = removed  │
│ • submitButton.opacity = 100%       │
│ • submitText = "Create"             │
│ • spinner.hidden = true             │
│ • cancelButton.disabled = false     │
│ • All inputs.disabled = false       │
└─────────────────────────────────────┘
```

## Validation Error Display Flow

```
Validation Error Received (422)
    │
    ▼
Parse errors object
    │
    ├─ For each field with errors:
    │   │
    │   ├─ Get first error message
    │   │
    │   ├─ Find input element by name
    │   │   └─ Add red border classes
    │   │
    │   └─ Find error element by ID
    │       └─ Show error text
    │
    ├─ Check for duplicate name error
    │   │
    │   └─ If found:
    │       └─ Show specific duplicate message
    │
    └─ Show general error summary
        │
        ├─ 1 error: "Please correct the error below."
        └─ N errors: "Please correct N errors below."
```

## Success Flow

```
Success Response (200)
    │
    ▼
Extract data.data (new item)
    │
    ├─ Update Dropdown
    │   │
    │   ├─ Create new option element
    │   ├─ Set value = item.id
    │   ├─ Set text = item.name
    │   ├─ Set selected = true
    │   │
    │   ├─ Find correct alphabetical position
    │   ├─ Insert option at position
    │   │
    │   ├─ Trigger change event
    │   └─ Highlight dropdown (green ring, 2s)
    │
    ├─ Show Success Message
    │   │
    │   ├─ Display green banner in modal
    │   ├─ Show "[Type] '[Name]' created successfully"
    │   └─ Announce to screen reader (polite)
    │
    ├─ Show Toast Notification
    │   │
    │   ├─ Check for global notification system
    │   ├─ Check for Alpine.js events
    │   └─ Fallback: Create simple toast
    │
    └─ Auto-close Modal
        │
        └─ After 1.5 seconds:
            ├─ Close modal
            └─ Restore focus to trigger button
```

## Retry Mechanism Flow

```
Network Error Detected
    │
    ▼
Check retry count
    │
    ├─ retryCount < 2?
    │   │
    │   ├─ YES:
    │   │   │
    │   │   ├─ Show retry message
    │   │   │   └─ "Network error. Retrying... (Attempt X of 2)"
    │   │   │
    │   │   ├─ Calculate backoff delay
    │   │   │   └─ delay = 1000ms * (retryCount + 1)
    │   │   │       • Attempt 1: 1 second
    │   │   │       • Attempt 2: 2 seconds
    │   │   │
    │   │   ├─ Wait for delay
    │   │   │
    │   │   ├─ Reset loading state
    │   │   │
    │   │   └─ Call submitForm(retryCount + 1)
    │   │       └─ Recursive retry
    │   │
    │   └─ NO:
    │       │
    │       └─ Show final network error
    │           └─ "Network error. Please check your internet connection and try again."
    │
    └─ Continue with normal error handling
```

## Key Features Illustrated

1. **Comprehensive Error Handling**: Every error type has specific handling
2. **Automatic Retry**: Network errors retry automatically with backoff
3. **Loading States**: Clear visual feedback during processing
4. **Validation Feedback**: Real-time and on-submit validation
5. **Success Feedback**: Multiple layers of success indication
6. **Accessibility**: Screen reader announcements throughout
7. **User Control**: Modal stays open on errors for retry
8. **Double Submission Prevention**: Button disabled during processing
