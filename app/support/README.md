# Customer Support Pages

This directory contains the customer-facing support ticket system pages.

## Pages

### 1. Tickets List (`tickets.php`)
- **Route**: `/app/support/tickets.php`
- **Requirements**: 7.2
- **Description**: Displays all support tickets for the logged-in customer
- **Features**:
  - Shows ticket ID, subject, category, priority, status, and last updated time
  - Provides "Create New Ticket" button
  - Empty state when no tickets exist
  - Responsive table layout

### 2. New Ticket (`tickets/new.php`)
- **Route**: `/app/support/tickets/new.php`
- **Requirements**: 7.1
- **Description**: Form to create a new support ticket
- **Features**:
  - Subject field (required)
  - Category dropdown (Technical, Billing, Account, Feature Request, General)
  - Priority dropdown (Low, Medium, High, Urgent)
  - Optional subscription linking
  - Description textarea (required)
  - CSRF protection
  - Form validation
  - Links ticket to current user automatically

### 3. Ticket Detail (`tickets/view.php`)
- **Route**: `/app/support/tickets/view.php?id={ticket_id}`
- **Requirements**: 7.3, 7.4, 7.5
- **Description**: View ticket details and message thread, add replies
- **Features**:
  - Displays ticket metadata (subject, status, priority, category, dates)
  - Shows message thread in chronological order (Requirement 7.3)
  - Customer and admin messages visually distinguished
  - Reply form for open tickets (Requirement 7.4)
  - Prevents replies to closed tickets (Requirement 7.5)
  - CSRF protection
  - Access control (customers can only view their own tickets)

## Security Features

- **Authentication**: All pages require active customer session
- **Authorization**: Customers can only view/manage their own tickets
- **CSRF Protection**: All forms include CSRF token validation
- **Input Sanitization**: All user input is sanitized and escaped
- **Access Control**: Ticket ownership verified before displaying details

## Database Tables Used

- `tickets`: Stores ticket metadata
- `ticket_messages`: Stores message thread
- `users`: For customer and admin information
- `subscriptions`: For optional ticket-subscription linking

## Services Used

- `TicketService`: Handles ticket CRUD operations
- `CsrfService`: Provides CSRF token generation and validation
- `TicketMessage` Model: Manages message operations
- `Subscription` Model: Fetches customer subscriptions

## Validation Rules

### New Ticket
- Subject: Required, max 255 characters
- Category: Required, must be valid option
- Priority: Required, defaults to MEDIUM
- Description: Required

### Reply
- Content: Required
- Ticket must not be closed
- User must own the ticket

## Status Flow

Tickets can have the following statuses:
- `OPEN`: Initial status when created
- `IN_PROGRESS`: Admin is working on it
- `WAITING_ON_CUSTOMER`: Awaiting customer response
- `RESOLVED`: Issue resolved
- `CLOSED`: Ticket closed (no more replies allowed)

## Priority Levels

- `LOW`: Non-urgent issues
- `MEDIUM`: Standard priority (default)
- `HIGH`: Important issues requiring prompt attention
- `URGENT`: Critical issues requiring immediate attention

## Categories

- Technical Issue
- Billing Question
- Account Management
- Feature Request
- General Inquiry

## Future Enhancements

- File attachment support for tickets and replies
- Email notifications on ticket updates
- Ticket search and filtering
- Ticket rating/feedback system
- Real-time updates using WebSockets
