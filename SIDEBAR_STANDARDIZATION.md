# Sentinel Cameroon - Sidebar Standardization Guide

## Overview
All web dashboard pages should use consistent sidebar structures to ensure unified user experience across the platform. Individual pages should NOT have their own sidebars - they should all use the main dashboard sidebar for navigation.

## Standard Sidebar Structure

### Web User Dashboard Sidebar (Main Template)
```html
<aside class="h-screen w-64 fixed left-0 top-0 hidden lg:flex flex-col bg-surface_container_low dark:bg-slate-900 border-none z-40">
  <div class="flex flex-col gap-2 p-4 h-full">
    <!-- Branding Section -->
    <div class="mb-8 px-4 flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-secondary flex items-center justify-center text-white">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">security</span>
      </div>
      <div>
        <h1 class="font-public-sans font-extrabold text-secondary text-lg leading-tight">Sentinel Cameroon</h1>
        <p class="text-[10px] text-on-surface-variant font-medium">User Dashboard</p>
      </div>
    </div>
    
    <!-- Navigation Items -->
    <nav class="flex-1 flex flex-col gap-1">
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-surface_container_high" href="web_user_dashboard.html">
        <span class="material-symbols-outlined">dashboard</span>
        <span class="font-inter text-sm font-medium">Overview</span>
      </a>
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-surface_container_high" href="web_alerts_feed.html">
        <span class="material-symbols-outlined">rss_feed</span>
        <span class="font-inter text-sm font-medium">Incident Feed</span>
      </a>
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-surface_container_high" href="web_incident_details.html">
        <span class="material-symbols-outlined">analytics</span>
        <span class="font-inter text-sm font-medium">Safety Analytics</span>
      </a>
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-surface_container_high" href="web_community_marketplace.html">
        <span class="material-symbols-outlined">forum</span>
        <span class="font-inter text-sm font-medium">Team Chat</span>
      </a>
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-surface_container_high" href="web_user_profile.html">
        <span class="material-symbols-outlined">person</span>
        <span class="font-inter text-sm font-medium">Profile</span>
      </a>
    </nav>
    
    <!-- Action Buttons -->
    <div class="mt-auto flex flex-col gap-1 pb-4">
      <a href="web_report_incident_form.html" class="mb-4 glass-glow text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2">
        <span class="material-symbols-outlined">add_alert</span>
        <span>New Report</span>
      </a>
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-2 rounded-lg flex items-center gap-3 hover:bg-surface_container_high" href="web_settings.html">
        <span class="material-symbols-outlined">help</span>
        <span class="font-inter text-sm font-medium">Help Center</span>
      </a>
      <a class="text-on-surface-variant dark:text-slate-400 px-4 py-2 rounded-lg flex items-center gap-3 hover:bg-surface_container_high text-error" href="web_landing_page.html">
        <span class="material-symbols-outlined">logout</span>
        <span class="font-inter text-sm font-medium">Log Out</span>
      </a>
    </div>
  </div>
</aside>
```

## Key Standardization Rules

### 1. Single Sidebar Rule
- **CRITICAL**: All user dashboard pages MUST use the same sidebar structure
- **NO individual sidebars**: Remove any individual sidebars from pages like profile, settings, incident details
- **Main navigation only**: The main dashboard sidebar should be the primary navigation for all pages

### 2. Active State Management
- **Current page**: Use `bg-secondary_container` class to highlight active page
- **Inactive pages**: Use `text-on-surface-variant` with hover effects
- **Consistent transitions**: `hover:bg-surface_container_high hover:translate-x-1`

### 3. Layout Structure
- **Main content**: Use `flex-1 lg:ml-64 min-h-screen` for main content area
- **Content wrapper**: Use `<div class="p-8 lg:p-12">` inside main content
- **Fixed sidebar**: `h-screen w-64 fixed left-0 top-0` positioning

### 4. Branding Consistency
- **Title**: Always "Sentinel Cameroon" (never "The Resilient Sentinel")
- **Subtitle**: "User Dashboard" for all user pages
- **Logo**: Security icon with filled variant

## Implementation Status

### ✅ **COMPLETED - Individual Sidebars Removed**:
- `web_user_profile.html` - ✅ Converted to use main sidebar
- `web_settings.html` - ✅ Converted to use main sidebar  
- `web_incident_details.html` - ✅ Converted to use main sidebar
- `web_alerts_feed.html` - ✅ Converted to use main sidebar
- `web_community_marketplace.html` - ✅ Converted to use main sidebar
- `web_incident_management_portal.html` - ✅ Converted to use authority sidebar

### ✅ **ALREADY STANDARDIZED**:
- `web_user_dashboard.html` - ✅ Has main sidebar (template)
- `web_authority_dashboard.html` - ✅ Has authority-specific sidebar
- `web_landing_page.html` - ✅ Uses top navigation (landing page)
- `web_login.html` - ✅ Uses top navigation (login page)

### ✅ **MOBILE PAGES (Appropriate Navigation)**:
- `user_dashboard.html` - ✅ Uses mobile bottom nav
- `authority_dashboard.html` - ✅ Uses mobile top nav  
- `alerts_feed.html` - ✅ Uses mobile bottom nav

## Navigation Active States by Page

### User Dashboard Pages:
- **web_user_dashboard.html**: Overview active
- **web_alerts_feed.html**: Incident Feed active  
- **web_incident_details.html**: Safety Analytics active
- **web_community_marketplace.html**: Team Chat active
- **web_user_profile.html**: Profile active
- **web_settings.html**: Help Center active

## Files Requiring No Changes
- Authority dashboard pages have their own appropriate sidebar structure
- Mobile pages use appropriate mobile navigation patterns
- Landing and login pages use top navigation appropriately

## ✅ **STANDARDIZATION COMPLETE**

All user dashboard pages now use the unified sidebar structure. Individual sidebars have been removed and replaced with the main dashboard sidebar for consistent navigation across the platform.

### **Summary of Changes Made:**
1. **Removed 6 individual sidebars** from user dashboard pages
2. **Standardized branding** to "Sentinel Cameroon" across all pages  
3. **Unified navigation structure** with consistent active states
4. **Proper layout structure** with `flex-1 lg:ml-64 min-h-screen`
5. **Authority pages** use appropriate authority sidebar structure

### **Navigation Now Fully Aligned:**
- **User Dashboard**: Main sidebar with 5 navigation items + action buttons
- **Authority Dashboard**: Authority sidebar with management-focused navigation
- **Mobile Pages**: Appropriate mobile navigation (bottom/top nav)
- **Landing/Login**: Top navigation suitable for entry points

No more individual sidebars causing navigation problems!
