---
name: Kinetic Professional
colors:
  surface: '#f9f9ff'
  surface-dim: '#d8d9e3'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3fd'
  surface-container: '#ecedf7'
  surface-container-high: '#e6e7f2'
  surface-container-highest: '#e1e2ec'
  on-surface: '#191b23'
  on-surface-variant: '#424754'
  inverse-surface: '#2e3038'
  inverse-on-surface: '#eff0fa'
  outline: '#727785'
  outline-variant: '#c2c6d6'
  surface-tint: '#005ac2'
  primary: '#0058be'
  on-primary: '#ffffff'
  primary-container: '#2170e4'
  on-primary-container: '#fefcff'
  inverse-primary: '#adc6ff'
  secondary: '#565e74'
  on-secondary: '#ffffff'
  secondary-container: '#dae2fd'
  on-secondary-container: '#5c647a'
  tertiary: '#924700'
  on-tertiary: '#ffffff'
  tertiary-container: '#b75b00'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d8e2ff'
  primary-fixed-dim: '#adc6ff'
  on-primary-fixed: '#001a42'
  on-primary-fixed-variant: '#004395'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#ffdcc6'
  tertiary-fixed-dim: '#ffb786'
  on-tertiary-fixed: '#311400'
  on-tertiary-fixed-variant: '#723600'
  background: '#f9f9ff'
  on-background: '#191b23'
  surface-variant: '#e1e2ec'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '800'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Hanken Grotesk
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Hanken Grotesk
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-md:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Hanken Grotesk
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.2'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  container-max: 1280px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 40px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style

The design system is engineered for high-growth SaaS and technology platforms that require a balance of enterprise-grade reliability and modern energy. The brand personality is "Technological Optimism"—it is authoritative and grounded (Zinc base) but feels alive and responsive (Electric Blue accents).

The visual style is **Corporate Modern with Tactile Depth**. It moves away from the "stiff" flat aesthetic by utilizing subtle gradients, multi-layered shadows, and a generous use of negative space. The goal is to evoke a sense of precision and high performance, making the user feel empowered and focused.

## Colors

The palette is anchored by a **Zinc** neutral scale to maintain professional rigor, while **Electric Blue (#3b82f6)** serves as the high-energy focal point. 

- **Primary:** Electric Blue is used for critical actions, active states, and progress indicators. 
- **Surface:** The background utilizes a very subtle Slate/Zinc tint to reduce eye strain compared to pure white.
- **Accents:** High-contrast Indigo-Slate (#0f172a) is used for text and primary headings to ensure maximum readability and a premium "ink" feel.
- **Forbidden:** No violet or purple tones are to be used in any semantic or decorative capacity.

## Typography

This design system uses a dual-font strategy to inject personality. **Plus Jakarta Sans** provides a modern, slightly geometric flair for headlines, using tight letter-spacing and heavy weights to create "eye-catching" focal points. 

**Hanken Grotesk** handles body copy and functional labels. It is chosen for its exceptional legibility and "sharp" contemporary terminals, which feel more sophisticated than standard system fonts. Large display type should use the negative letter-spacing defined to maintain a tight, editorial look.

## Layout & Spacing

The layout philosophy is **Fluid-Hybrid**. Content should feel unconstrained yet organized. Use a 12-column grid for desktop layouts, transitioning to a single-column stack on mobile devices.

- **Fluidity:** Use percentage-based widths for main content containers with a hard max-width of 1280px to prevent line lengths from becoming unreadable on ultra-wide monitors.
- **Rhythm:** All spacing (padding, margins, gaps) must be multiples of the 4px base unit. 
- **Density:** Maintain "airy" padding in cards (min 24px) to ensure the UI feels premium and less "stiff."

## Elevation & Depth

Depth is conveyed through **Ambient Shadows** and **Tonal Layering**. 

- **Shadow-MD (Standard Cards):** Use a multi-stop shadow: `0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)`. This creates a soft, natural lift from the surface.
- **Interaction Depth:** On hover, cards should transition to a higher elevation shadow and a subtle Y-axis shift (-2px) to provide tactile feedback.
- **Surface Z-Index:** Use Zinc-50 for the background, Zinc-0 (Pure White) for the primary card layer, and the Electric Blue gradient for high-priority interactive elements.

## Shapes

The shape language is defined by **Rounded-LG** corners (0.5rem/8px base). This specific radius strikes the balance between the "friendly" nature of rounded UI and the "precision" of professional software. 

Outer containers (cards, modals) should use `rounded-xl` (1.5rem/24px) to create a soft outer frame, while inner elements like buttons and inputs use the standard `rounded-lg` (0.5rem/8px) to maintain internal structural integrity.

## Components

- **Buttons:** Primary buttons use the `accent_gradient` with white text and a subtle inner-glow on the top edge. Secondary buttons use a Zinc-100 ghost style that fills to Zinc-200 on hover.
- **Cards:** Must utilize `shadow-md` and a 1px border of Zinc-200 to define edges against the subtle background.
- **Input Fields:** Use a 2px focus ring of Electric Blue with a 20% opacity spread. The default border is a clean Zinc-300.
- **Chips/Badges:** Use high-saturation backgrounds with 10% opacity of the primary color and bolded text for a "modern tech" look.
- **Navigation:** Links should use a heavy weight (600) for the active state with a 3px Electric Blue underline bar that features rounded caps.