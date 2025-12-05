# AFM Demo Data Setup

## Overview
The AFM system uses **5 demo students** from Sim-SIS as the single source of truth for all demo data.

## Demo Students
- **2024001** - Ali Ahmed (CS)
- **2024002** - Sara Salem (CS)
- **2024003** - Omar Khaled (General)
- **2024004** - Fatima Yusuf (Management)
- **2024005** - Hassan Ali (SE)

These students are already seeded in the `sis_students` and `sis_enrollments` tables.

## Seeding Demo Data

### Reset and Seed (Clean Start)
```bash
# 1. Reset AFM data (removes old completion flags and course refs)
php artisan db:seed --class=AfmDemoDataResetSeeder

# 2. Seed AFM data aligned with Sim-SIS students
php artisan db:seed --class=SimSisAfmDemoSeeder
```

### What Gets Seeded
- **Form**: 1 active course feedback form
- **Courses**: 4 courses (synced from SIS enrollments to `sis_course_ref`)
- **Completion Flags**: 11 flags (one per student enrollment)
  - Ali (2024001): All 3 completed
  - Sara (2024002): 1 of 2 completed
  - Others: Pending

### Expected QA Overview Metrics
After seeding:
- **Total Active Students**: 5
- **Participation Rate**: ~27% (3 of 11 completed)
- **Pending Evaluations**: 8
- **High-Risk Courses**: Varies based on threshold

## Test-Only Seeder

`QaOverviewTestDataSeeder` creates 40 synthetic students (IDs 100001-100040) for **testing only**.

**DO NOT** use this for demo environment. It will show incorrect student counts.

## Single Source of Truth

All systems now reference the same 5 students:
- ✅ Sim-SIS UI (`/sim-sis`)
- ✅ SSO payloads
- ✅ `sis_course_ref` table
- ✅ `completion_flags` table
- ✅ QA Overview metrics (`/qa`)
