# TODO: Revert iPhone Image Fixes (approved plan)

**Status:** Starting

## Steps:
1. [ ] Edit assets/css/style.css - Logo/nav sizes to fixed px/rem (remove clamp/vw)
2. [ ] Edit assets/css/style.css - Object-fit adjustments (scale-down for logos)
3. [ ] Edit assets/css/style.css - Navbar padding (remove env(safe-area), fixed)
4. [ ] Edit components/head.php - Ensure standard viewport meta
5. [ ] Test: Hard refresh desktop/iPhone, check logo/navbar/images
6. [ ] Mark complete, rm this file

**Goal:** Restore original desktop styling, keep basic mobile compat.

