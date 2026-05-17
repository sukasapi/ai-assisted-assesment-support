---
name: AIS-ANALYZER
colors:
  surface: '#fbf8ff'
  surface-dim: '#dad9e3'
  surface-bright: '#fbf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f2fd'
  surface-container: '#eeedf7'
  surface-container-high: '#e8e7f1'
  surface-container-highest: '#e3e1ec'
  on-surface: '#1a1b22'
  on-surface-variant: '#47464b'
  inverse-surface: '#2f3038'
  inverse-on-surface: '#f1effa'
  outline: '#77767b'
  outline-variant: '#c8c5cb'
  surface-tint: '#5f5e61'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#1b1b1e'
  on-primary-container: '#858387'
  inverse-primary: '#c8c5ca'
  secondary: '#712ae2'
  on-secondary: '#ffffff'
  secondary-container: '#8a4cfc'
  on-secondary-container: '#fffbff'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#002114'
  on-tertiary-container: '#069669'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e4e1e6'
  primary-fixed-dim: '#c8c5ca'
  on-primary-fixed: '#1b1b1e'
  on-primary-fixed-variant: '#47464a'
  secondary-fixed: '#eaddff'
  secondary-fixed-dim: '#d2bbff'
  on-secondary-fixed: '#25005a'
  on-secondary-fixed-variant: '#5a00c6'
  tertiary-fixed: '#85f8c4'
  tertiary-fixed-dim: '#68dba9'
  on-tertiary-fixed: '#002114'
  on-tertiary-fixed-variant: '#005137'
  background: '#fbf8ff'
  on-background: '#1a1b22'
  surface-variant: '#e3e1ec'
typography:
  display:
    fontFamily: Hanken Grotesk
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  section-header:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
  body-base:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-muted:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label:
    fontFamily: Hanken Grotesk
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
  code:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  sidebar_width: 64px
  container_max_width: 1152px
  gutter: 24px
  stack_sm: 8px
  stack_md: 16px
---

## Brand & Style
The design system is engineered for high-density assessment analysis, prioritizing cognitive ease and task efficiency. The brand personality is professional, calm, and highly objective, reflecting the precision required in analytical work. 

The visual style follows a **Corporate Modern** aesthetic with **Minimalist** principles. It utilizes a restrained neutral palette to reduce visual noise, allowing the data to remain the focal point. AI-driven features are subtly distinguished through a specific violet accent, signaling assistance without disrupting the user's flow. The overall experience should feel reliable, systematic, and intellectually grounded.

## Colors
The color strategy employs a Zinc-based grayscale to establish a sophisticated, neutral foundation. 

- **Primary:** Zinc-900 is used for high-contrast elements and primary actions to signify authority.
- **AI Accents:** Violet is reserved exclusively for AI-assisted insights and automated features, using a soft violet-50 background and violet-300 borders.
- **Status Indicators:** A semantic system is used for feedback—Emerald for successful finalization, Amber for warnings, and Rose for critical errors or required fields.
- **Surface & Borders:** Surfaces primarily use White and Zinc-50. Borders use Zinc-200 for soft separation.

## Typography
The system uses **Hanken Grotesk** for all UI elements to maintain a sharp, contemporary, and highly legible interface. 

- **Titles:** Use `display` (2xl) for main page headings to establish clear entry points.
- **Sectioning:** Section headers are rendered in `sm` size, semi-bold weight, and uppercase with increased tracking to create distinct visual breaks between data sets.
- **Content:** The primary body text is 14px (sm) for optimal information density. Zinc-800 is used for high readability, while Zinc-600 is used for secondary descriptions.
- **Data/Technical:** **JetBrains Mono** is utilized for any code snippets, raw data IDs, or formulaic content to differentiate technical strings from narrative text.

## Layout & Spacing
The layout is structured around a fixed sidebar and a contained fluid main content area.

- **Navigation:** A compact 64px sidebar houses icon-based navigation, maximizing the horizontal space for data analysis.
- **Containment:** Main content is centered within a `max-w-6xl` (1152px) container to maintain readable line lengths and prevent data sprawl on ultra-wide monitors.
- **Rhythm:** A strict 4px/8px baseline grid is used. 16px is the standard padding for cards and containers, while 8px is used for internal component grouping.
- **Adaptation:** On smaller screens, the sidebar remains fixed, and the main container shifts to full-width with 16px side margins.

## Elevation & Depth
The design system utilizes **Low-contrast outlines** combined with **Tonal layers** to establish hierarchy.

- **Base Layer:** The application background is Zinc-50, providing a subtle contrast against white components.
- **Containers:** Cards and primary sections use a White background with a 1px Zinc-200 border and a `shadow-sm` (subtle, low-blur shadow) to lift them slightly from the base.
- **Interactions:** Hover states on interactive elements use a slight darkening of the background (e.g., Zinc-50 to Zinc-100) rather than increased shadow depth, maintaining a flat, professional feel.

## Shapes
The shape language is **Soft**, emphasizing precision and structural integrity.

- **Components:** Buttons, inputs, and cards use a 0.25rem (4px) corner radius.
- **Inner Elements:** Chips and badges may use the same radius to maintain a consistent "modular" appearance. 
- **Consistency:** Avoid pill-shaped elements (except for specific status dots) to keep the UI aligned with its analytical, task-oriented purpose.

## Components
- **Primary Buttons:** Solid Zinc-900 background with White text. Used for the main "Call to Action" on any page.
- **AI Buttons:** Violet-50 background, Violet-300 border, and Violet-900 text. Use these for triggers that involve automated analysis or "Generate" functions.
- **Finalization Buttons:** Emerald-600 background with White text. Used exclusively for "Submit," "Finish," or "Approve" actions.
- **Inputs:** Zinc-300 borders with a 2px Zinc-500 ring on focus. Placeholder text should be Zinc-400.
- **Tables:** Headers use a Zinc-50 background with semi-bold Zinc-900 text. Rows are separated by 1px Zinc-100 horizontal dividers. 
- **Cards:** White surface, Zinc-200 border, and `shadow-sm`. Used to group related assessment metrics or form sections.
- **Badges/Chips:** Used for status (Final, Draft, Pending). Follow the semantic color system (Emerald, Amber, Zinc).