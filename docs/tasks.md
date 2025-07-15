# CRM Simple - Task List

This document contains a list of tasks to be implemented based on the improvement plan in `plan.md`. After completing each task, mark it as done by changing the checkbox from [ ] to [x].

## 1. Data Architecture Improvements

### 1.1 Entity Relationships
- [x] Implement proper cascade operations for entity relationships
- [x] Add indexes on frequently queried fields (e.g., Account.name, Action.nextStepDate)
- [x] Ensure proper validation constraints on all entity fields
- [x] Add soft delete functionality for all entities to maintain data history

### 1.2 Data Validation
- [x] Implement comprehensive validation rules for all entity properties
- [x] Add custom validators for business-specific rules (e.g., priority values)
- [x] Ensure proper error handling and user feedback for validation failures

## 2. Security Enhancements

### 2.1 Authentication System
- [ ] Implement the required email/password authentication system
- [ ] Set up TOTP-based two-factor authentication (2FA)
- [ ] Configure proper password policies (complexity, expiration)
- [ ] Implement CSRF protection on all forms

### 2.2 Authorization Framework
- [ ] Configure role-based access control using Symfony's security system
- [ ] Implement voter components for fine-grained permission control
- [ ] Secure all API endpoints and controller actions

## 3. User Interface Improvements

### 3.1 Responsive Design
- [ ] Implement the two-column layout as specified in requirements
- [ ] Ensure mobile responsiveness using Bootstrap 5's grid system
- [ ] Optimize UI components for different screen sizes

### 3.2 Interactive Features
- [ ] Implement client-side interactivity using Vanilla JS or Stimulus
- [ ] Create dynamic loading of Actions when clicking on Account rows
- [ ] Implement dynamic loading of History when clicking on Action rows
- [ ] Add sorting and filtering capabilities to all tables

## 4. Performance Optimization

### 4.1 Database Queries
- [ ] Optimize Doctrine queries to minimize database load
- [ ] Implement pagination for large result sets
- [ ] Use query caching where appropriate
- [ ] Configure proper indexing strategy

### 4.2 Frontend Performance
- [ ] Optimize asset loading through CDN as specified
- [ ] Implement lazy loading for dynamic content
- [ ] Minimize DOM manipulations in JavaScript code

## 5. Development Workflow

### 5.1 Testing Strategy
- [ ] Implement unit tests for all business logic
- [ ] Add functional tests for critical user flows
- [ ] Set up end-to-end tests for key features
- [ ] Configure CI/CD pipeline for automated testing

### 5.2 Code Quality
- [ ] Establish coding standards and style guides
- [ ] Implement static code analysis tools
- [ ] Set up code review processes
- [ ] Document all major components and functions

## 6. Deployment Strategy

### 6.1 Environment Configuration
- [ ] Configure environment-specific settings (.env files)
- [ ] Set up proper production environment optimizations
- [ ] Implement logging and monitoring solutions
- [ ] Create backup and recovery procedures

### 6.2 Maintenance Plan
- [ ] Schedule regular security updates
- [ ] Plan for periodic performance reviews
- [ ] Establish a process for feature enhancements
- [ ] Create documentation for system administrators
