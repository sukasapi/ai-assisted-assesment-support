AI Assessment Support — Development Master Plan (Laravel 12)
Project Overview

Aplikasi assessment kompetensi berbasis Laravel 12 yang membantu assessor melakukan:

input evidence per tool,
validasi key behavior,
integrasi kompetensi,
analisa GAP,
AI-assisted recommendation,
report generator,
export PDF.

AI hanya bertindak sebagai assistant recommendation, bukan penentu nilai final.

1. Technology Stack
Backend
Laravel 12
PHP 8.3+
MySQL
Laravel Sanctum
Queue + Job
Laravel Policy / Middleware
Frontend
Blade
TailwindCSS
AlpineJS
SweetAlert2
AI
OpenRouter API
Modular AI Service Layer
Prompt Versioning
Export & Reporting
DomPDF / SnappyPDF
2. Core Principles
Assessment Governance
Nilai final hanya ditentukan assessor.
AI hanya memberikan suggestion.
Semua hasil AI dapat ditolak/edit.
Semua AI response harus dapat diaudit.
Data Governance
Hindari PII dalam prompt AI.
Prompt versioning wajib.
Activity logging wajib untuk aksi admin.
Versioning Governance
Matrix kompetensi-tool harus versioned.
Assessment menyimpan snapshot matrix version.
3. Final MVP Scope
Authentication & Authorization

Roles:

admin
konsultan

Fitur:

login/logout
role middleware
policy access
session auth
sanctum token support
4. Database Architecture
Prefix Standard

Semua tabel menggunakan prefix:

ais_
5. Master Tables
Users
ais_users
Participants
ais_peserta

Fields:

participant_code
full_name
email
position
pendidikan
birth_date
notes
is_active
matrix_version_id
Competencies
ais_kompetensi

Fields:

competency_code
name
category
max_level
is_active
Competency Levels
ais_level_kompetensi

Fields:

competency_id
level
behavioral_indicator
label
description
Assessment Tools
ais_tools_assessment

Tools:

BEI
LGD
PA
INT
INTRAY
RP
CASE
6. Matrix Versioning System
Matrix Versions
ais_matrix_versions

Fields:

version_code
version_name
is_active
is_default
published_at
Competency Tool Mapping
ais_mapping_kompetensi_tools

Fields:

matrix_version_id
competency_id
assessment_tool_id
is_required
weight
is_active

Unique Key:

(matrix_version_id, competency_id, assessment_tool_id)
7. Assessment Lifecycle
Assessments
ais_assessments

Status:

draft
in_progress
integrated
finalized

Fields:

participant_id
matrix_version_id
status
finalized_by
finalized_at
Assessment Assessors
ais_assessment_assessors
Assessment Tool Selections
ais_assessment_tool_selections
8. Evidence & Key Behavior
Assessment Evidences
ais_bukti_penilaian

Fields:

assessment_id
assessment_tool_id
competency_id
evidence_text
ai_level
ai_reason
ai_confidence
ai_payload
ai_rated_at
Assessment Key Behaviors
ais_key_behaviors

Fields:

assessment_id
assessment_tool_id
competency_id
evidence_id
behavior_text
is_validated
validated_by
9. Competency Integration Engine
Competency Integrations
ais_integrasi_kompetensi

Purpose:

preview scoring
gap analysis
weighted integration
AI recommendation support

IMPORTANT:

bukan nilai final assessor
hanya preview integration
10. AI Architecture
AI Service Layer

Directory:

app/Services/AI

Structure:

AIProviderInterface
OpenRouterProvider
PromptManager
ResponseParser
AISuggestionService
AI Features
Evidence Summarizer

Input:

raw evidence

Output:

concise behavioral summary
KB Suggestion

Input:

evidence
competency

Output:

suggested key behaviors
Indicator Suggestion

Input:

competency
evidence

Output:

suggested competency level
AI Rating

Output:

suggested level
confidence
reasoning
11. AI Governance
Logging
ais_ai_logs

Fields:

assessment_id
prompt_version
provider
model
tokens
raw_response
parsed_response
latency
status
Protection Rules
timeout
retry
fallback error handling
rate limiting
feature flag AI enable/disable
12. Admin Features
Competency Matrix Admin

Route:

/competency-tool-matrix

Features:

grid matrix
filter kategori
required/optional toggle
weight setting
clone version
save bulk matrix
Activity Logs
/activity-logs

Log:

matrix.update
matrix.clone
participant.import
assessment.finalized
13. Participant Import CSV
Endpoint
POST /participants/import-csv

Supported:

comma delimiter
semicolon delimiter

Mode:

upsert by participant_code
14. Assessment Detail UI
Layout

Left Sidebar:

selected tools

Right Content:

competency tabs
Per Competency x Tool

Features:

evidence input
save evidence
AI rating button
AI reasoning panel
confidence indicator
15. Validation Rules
Assessment Creation

Selected tools harus memenuhi:

mapping competency-tool is_required=true
Evidence Input

Evidence hanya boleh:

tool yang dipilih assessment
KB Validation

KB hanya valid jika:

tool evidence dimapping ke competency
Integration

Preview score hanya menghitung:

KB yang lolos mapping aktif
16. Dashboard MVP
Widgets
Competency Table
competency
score
target
GAP
Job Fit %

Formula:

(total achieved / total target) * 100
Recommendation

Possible:

Fit
Development
Not Fit
AI Summary
strengths
weaknesses
recommendation draft

Editable by assessor.

17. Reporting System
Features
narrative generator
editable summary
competency summary
GAP analysis
recommendation
export PDF
18. Seeder Strategy
MasterDataSeeder

Seed:

assessment tools
competencies
competency levels
CompetencyToolMatrixSeeder

Seed:

baseline matrix mapping
AssessmentSampleSeeder

Seed:

demo assessment flow
evidences
KB sample
AI sample result
19. Folder Architecture
Suggested Structure
app/
├── Actions/
├── DTOs/
├── Enums/
├── Http/
├── Models/
├── Policies/
├── Repositories/
├── Services/
│   ├── AI/
│   ├── Assessment/
│   ├── Integration/
│   └── Reporting/
├── Support/
20. Development Phases
PHASE 1 — Foundation
Goals
auth
roles
master data
matrix system
Deliverables
Sanctum auth
role middleware
CRUD master
matrix versioning
activity logs
PHASE 2 — Assessment Core
Goals
assessment flow
evidence input
KB validation
Deliverables
assessment lifecycle
evidence per tool x competency
AI rating button
KB management
PHASE 3 — AI Integration
Goals
OpenRouter integration
AI logging
AI suggestion engine
Deliverables
summarizer
KB suggestion
AI indicator suggestion
AI governance
PHASE 4 — Integration Engine
Goals
competency integration
GAP analysis
job fit
Deliverables
integration service
weighted scoring
dashboard preview
PHASE 5 — Reporting
Goals
report builder
PDF export
Deliverables
narrative generator
final report
export PDF
PHASE 6 — QA & Stabilization
Goals
testing
auditability
optimization
Deliverables
feature tests
regression tests
queue optimization
logging verification
21. Testing Strategy
Feature Tests
auth
assessment flow
matrix validation
KB validation
report generation
Regression Tests
matrix version compatibility
existing assessment integrity
AI Tests
fallback handling
timeout handling
parser validation
22. Suggested Priority Order
Highest Priority
Auth & Roles
Master Data
Matrix Versioning
Assessment Flow
Evidence Input
AI Suggestion
Integration Engine
Dashboard
Reporting
PDF Export
23. Final MVP Success Criteria

MVP dianggap selesai jika:

✅ Assessor dapat membuat assessment end-to-end
✅ Evidence dapat diinput per tool x kompetensi
✅ AI dapat memberikan recommendation
✅ AI recommendation dapat diedit/ditolak
✅ Integrasi kompetensi berjalan
✅ Dashboard GAP & Job Fit tampil
✅ Report dapat diedit assessor
✅ Export PDF berhasil
✅ Semua keputusan final tetap manual assessor
✅ Semua AI activity dapat diaudit