# Aakaari Reseller Admin Guide

## Quick Start

### Accessing the Admin Dashboard
1. Navigate to `/admindashboard/` on your site
2. Login with administrator credentials
3. You'll see the admin dashboard with overview statistics

## Managing Reseller Applications

### Viewing Applications
1. Click on "Applications" tab in the sidebar
2. You'll see a list of all applications with their status
3. Use the filter dropdown to view specific statuses:
   - All Applications
   - Pending (awaiting review)
   - Approved (active resellers)
   - Rejected (declined applications)

### Reviewing an Application
1. Click the "Review" button next to any application
2. A modal will open showing application details:
   - Full name and contact information
   - Business details
   - Location
   - Applied date
   - Current status
3. Click "View Full Application in WP Admin" to see uploaded documents

### Approving an Application
1. Open the application review modal
2. Review all details carefully
3. Click the green "Approve" button
4. The system will:
   - Change status to "approved"
   - Update user's onboarding status
   - Send approval email to the applicant
   - Grant dashboard access

### Rejecting an Application
1. Open the application review modal
2. Enter a clear rejection reason in the text area
   - Example: "Incomplete KYC documents. Please provide valid ID proof."
3. Click the red "Reject" button
4. The system will:
   - Change status to "rejected"
   - Save the rejection reason
   - Send rejection email with reason
   - User will see rejection message on login

## Managing Resellers

### Viewing Reseller List
1. Click on "Resellers" tab in the sidebar
2. View all registered resellers with:
   - Name and contact details
   - Total orders and revenue
   - Commission earned
   - Join date
   - Current status

### Reseller Status Types
- **Active**: Approved and can use the platform
- **Pending**: Application under review
- **Suspended**: Account temporarily disabled
- **Inactive**: Rejected or removed

### Suspending a Reseller
1. In the Resellers tab, find the user
2. Click the three-dot menu (⋮) on the right
3. Select "Suspend Account"
4. Confirm the action
5. The system will:
   - Change application status to "suspended"
   - Send suspension notification email
   - Block dashboard access (shows suspension message)

### Activating a Suspended Reseller
1. In the Resellers tab, find the suspended user
2. Click the three-dot menu (⋮)
3. Select "Activate Account"
4. Confirm the action
5. The system will:
   - Change application status to "approved"
   - Send activation email
   - Restore dashboard access

### Viewing User Details
1. Click the three-dot menu (⋮) next to any reseller
2. Select "View Details"
3. You'll be taken to WordPress user edit page
4. Here you can:
   - Edit user information
   - View user meta data
   - Reset password
   - Change role

### Viewing User Orders
1. Click the three-dot menu (⋮) next to any reseller
2. Select "View Orders"
3. You'll see all orders placed by that reseller
4. (Feature to be fully implemented)

## Understanding the Dashboard

### Overview Tab
Shows key statistics:
- **Total Resellers**: All registered users with reseller role
- **Total Orders**: All orders in the system
- **Total Revenue**: Sum of all order values
- **Pending Applications**: Applications awaiting review

Plus:
- Recent orders from all resellers
- Pending applications list (quick access)

### Applications Tab
- Complete list of all applications
- Filterable by status
- Sortable by date
- Quick review access

### Resellers Tab
- All registered resellers
- Search functionality
- Filter options
- Action menu for each user

### Orders Tab
- All platform orders
- Order details and status
- Payment information
- Export capability

### Products, Payouts, Settings Tabs
- Placeholder for future features
- Will be implemented in later phases

## Application Statuses Explained

### Pending
- **Meaning**: Application submitted, awaiting admin review
- **User Experience**: Shows "Application Under Review" message
- **Admin Action**: Review and approve/reject
- **Duration**: Typically 24-48 hours

### Approved
- **Meaning**: Application reviewed and accepted
- **User Experience**: Full dashboard access, can place orders
- **Admin Action**: Can suspend if needed
- **Benefits**: Active reseller with all features

### Rejected
- **Meaning**: Application not accepted
- **User Experience**: Shows rejection message with reason
- **Admin Action**: User can contact support or reapply
- **Next Steps**: User should address concerns and reapply

### Suspended
- **Meaning**: Account temporarily disabled
- **User Experience**: Shows suspension message, no dashboard access
- **Admin Action**: Can activate to restore access
- **Reasons**: Policy violation, suspicious activity, compliance issues

## Email Notifications

The system automatically sends emails for:

### Application Submitted
- **To**: Admin
- **When**: User submits application
- **Contains**: Applicant details, admin link

### Application Approved
- **To**: Applicant
- **When**: Admin approves application
- **Contains**: Congratulations, login link

### Application Rejected
- **To**: Applicant
- **When**: Admin rejects application
- **Contains**: Rejection reason, support contact

### Account Suspended
- **To**: User
- **When**: Admin suspends account
- **Contains**: Suspension notice, support contact

### Account Activated
- **To**: User
- **When**: Admin activates suspended account
- **Contains**: Activation confirmation, login link

## Best Practices

### Application Review
1. ✅ Review all details carefully before approval
2. ✅ Verify contact information is valid
3. ✅ Check uploaded ID documents
4. ✅ Provide clear rejection reasons if declining
5. ✅ Respond within 24-48 hours

### User Management
1. ✅ Monitor user activity regularly
2. ✅ Investigate suspicious behavior
3. ✅ Communicate clearly with suspended users
4. ✅ Document reasons for suspension
5. ✅ Be fair and consistent in decisions

### Security
1. ✅ Keep admin credentials secure
2. ✅ Review applications from same IP carefully
3. ✅ Watch for duplicate submissions
4. ✅ Verify business information when possible
5. ✅ Report fraudulent applications

## Troubleshooting

### Application Not Appearing
**Problem**: Application submitted but not in admin dashboard
**Solution**:
1. Check if post type `reseller_application` exists
2. Verify taxonomy is registered
3. Check WordPress permalinks (resave)
4. Look in WordPress Posts → Reseller Applications

### User Can't Login After Approval
**Problem**: User approved but can't access dashboard
**Solution**:
1. Verify application status is "approved"
2. Check user's `onboarding_status` meta is "completed"
3. Ensure user has "reseller" role
4. Have user clear browser cache

### Emails Not Sending
**Problem**: Notification emails not being received
**Solution**:
1. Check WordPress email settings
2. Verify SMTP configuration
3. Test with WP Mail SMTP plugin
4. Check spam folders
5. Verify email addresses are correct

### Statistics Not Showing
**Problem**: Dashboard shows 0 or incorrect statistics
**Solution**:
1. Ensure WooCommerce is active (for order stats)
2. Check if users have correct role
3. Verify applications have taxonomy terms set
4. Refresh the page

## Quick Reference

### Keyboard Shortcuts
- `Esc`: Close modal dialogs
- Click outside: Close dropdowns

### Status Badge Colors
- 🟡 **Yellow**: Pending
- 🟢 **Green**: Approved/Active
- 🔴 **Red**: Rejected
- ⚫ **Gray**: Suspended

### Important Links
- Admin Dashboard: `/admindashboard/`
- WordPress Admin: `/wp-admin/`
- Reseller Applications CPT: `/wp-admin/edit.php?post_type=reseller_application`
- Users Management: `/wp-admin/users.php`

## Getting Help

### Support Resources
1. **Documentation**: Read IMPLEMENTATION_SUMMARY.md
2. **Technical Issues**: Check WordPress error logs
3. **Feature Requests**: Contact development team
4. **Bug Reports**: Include detailed steps to reproduce

### Common Questions

**Q: Can I approve multiple applications at once?**
A: Not yet, but bulk actions are planned for future update.

**Q: How do I export application data?**
A: Use WordPress admin → Reseller Applications → Export or export plugin.

**Q: Can users reapply after rejection?**
A: Yes, users can submit a new application after addressing the issues.

**Q: What happens when I suspend a user?**
A: They lose dashboard access and see a suspension message. No data is deleted.

**Q: Can I customize email notifications?**
A: Currently no, but customizable templates are planned.

## Updates & Maintenance

### Regular Tasks
- [ ] Daily: Review pending applications
- [ ] Weekly: Check user activity
- [ ] Monthly: Review suspended accounts
- [ ] Quarterly: Audit approved resellers

### Version History
- **v1.0 (Oct 2025)**: Initial implementation
  - Application workflow
  - Status management
  - User management
  - Email notifications

## Contact & Support

For technical support or feature requests, contact the development team with:
- Detailed description of the issue
- Screenshots if applicable
- Steps to reproduce
- Expected vs actual behavior

---

**Last Updated**: October 20, 2025
**Version**: 1.0
