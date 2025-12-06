# User Restrictions System

This document outlines the user restrictions system implemented for the PL Tournament application, designed for proof-of-concept deployments with limited resources.

## Overview

The restrictions system is controlled by the `APP_RESTRICTIONS_ENABLED` environment variable. When enabled, it applies various limitations to protect against resource overuse while maintaining core functionality.

## Configuration

Add to your `.env` file:
```
APP_RESTRICTIONS_ENABLED=true
```

To disable restrictions (development/production mode):
```
APP_RESTRICTIONS_ENABLED=false
```

## Restrictions Implemented

### 1. User Registration & Approval
- **Restriction**: New user registrations require manual approval
- **Behavior**: 
  - Users can register but cannot log in until approved
  - Registration page shows approval notice banner
  - Users redirected to login with "awaiting approval" message
- **Management**: Use the `user:approve` command to manage approvals

### 2. Tournament Creation Limit
- **Restriction**: Maximum 3 tournaments per user
- **Behavior**: Tournament creation blocked after limit reached
- **Error Message**: "You have reached the maximum number of tournaments (3) you can create."

### 3. Avatar Change Limit
- **Restriction**: Maximum 3 avatar changes per user
- **Tracking**: `avatar_changes_count` field tracks usage
- **Behavior**: Avatar upload/crop blocked after limit reached
- **Error Message**: "You have reached the maximum number of avatar changes (3) allowed."

## Database Changes

New fields added to `users` table:
- `avatar_changes_count` (integer, default: 0)
- `is_approved` (boolean, default: true)
- `approved_at` (timestamp, nullable)
- `approval_token` (string, nullable)

## Management Commands

### List Pending Approvals
```bash
php artisan user:approve list
```

### Approve a User
```bash
php artisan user:approve approve user@example.com
```

### Revoke Approval
```bash
php artisan user:approve disapprove user@example.com
```

## Email Notifications

When a user is approved, they automatically receive an email notification with login instructions.

### Email Service Setup (Mailgun)

1. Sign up for free Mailgun account (100 emails/day)
2. Add to `.env`:
```
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-secret-key
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="PL Tournament"
```

## Middleware

The `CheckUserApproval` middleware automatically:
- Checks if restrictions are enabled
- Logs out unapproved users
- Redirects to login with error message

Applied to all protected routes via `user.approval` middleware alias.

## User Model Methods

- `canCreateTournament()`: Checks tournament creation limit
- `canChangeAvatar()`: Checks avatar change limit
- `isApproved()`: Checks approval status
- `approve()`: Approves user and sends notification
- `incrementAvatarChanges()`: Tracks avatar changes

## Additional Restrictions Suggestions

Consider implementing these additional restrictions:
- **Daily Action Limits**: Limit picks/actions per day
- **Session Time Limits**: Auto-logout after inactivity
- **File Upload Limits**: Stricter file size/type restrictions
- **API Rate Limiting**: Prevent excessive API calls
- **Database Query Limits**: Limit complex operations
- **Storage Usage Tracking**: Monitor user storage consumption

## Employer Demonstration Points

This system demonstrates:
- **Resource Management**: Proactive protection against resource abuse
- **Scalable Architecture**: Easy to enable/disable based on environment
- **User Experience**: Clear messaging and graceful degradation
- **Administrative Control**: Command-line tools for user management
- **Email Integration**: Automated notifications with professional templates
- **Database Design**: Proper tracking and constraints
- **Middleware Usage**: Clean separation of concerns
- **Laravel Best Practices**: Following framework conventions
