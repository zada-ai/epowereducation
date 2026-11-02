# Ramom Paper Maker — PDF/Template Fix

This update fixes:

- HTTP 500 during paper PDF download on Ramom installations using different mPDF wrappers/versions.
- A4 PDF generation is now configured at mPDF construction time when the Composer mPDF class is available.
- The selected template is loaded for the saved paper, otherwise the branch default template is used.
- Template header content is rendered directly from `pm_templates.header_config`.
- Ramom logo path detection supports the common `uploads/app_image/logo.*` locations.
- Subject and class names are shown in the PDF instead of numeric IDs.
- MCQ options remain in one horizontal row using a fixed four-column table.

## Database

For an existing installation, run `sql/upgrade_pdf_header.sql` once. It adds `pm_templates.header_config` only when the column is missing.

For Urdu font support, also run `sql/upgrade_urdu_font.sql` once (adds `pm_templates.pdf_font`, safe to re-run). Then copy the font file(s) below into your deployed `assets/fonts/` folder alongside `assets/css` and `assets/js` (same deployment convention as the CSS/JS files). In the Templates screen, set "PDF Font" to the matching option on any template you want to render in Urdu — the question text itself must actually be typed in Urdu for this to have any visible effect.

Two Urdu font options are available:

- **Jameel Noori Nastaleeq** (`assets/fonts/Jameel_Noori_Nastaleeq_Regular.ttf`) — classic, widely-used Nastaliq font, but built for legacy ligature-substitution rendering, so some PDF engines (including mPDF) can render it imperfectly.
- **Noto Nastaliq Urdu** (`assets/fonts/NotoNastaliqUrdu-Regular.ttf` and `NotoNastaliqUrdu-Bold.ttf`) — Google's Noto font, built with standard OpenType shaping designed for automated PDF/text engines. Recommended if Jameel Noori Nastaleeq isn't rendering correctly on your server. Licensed under the SIL Open Font License.

If either font file isn't found at any of the checked paths, PDF export silently falls back to the default (English/Latin) font and logs an error line starting `Paper Maker: font file ... was not found` — check your Ramom error log if Urdu text isn't appearing.

If the column already exists, no change is made.

## Deployment

Replace the add-on controller/model/view files and assets from this ZIP using the same method you used for the previous Paper Maker update.

Then clear Ramom/application cache if enabled and reload the Paper Maker pages.

## Round 2 fixes: table layout regression + CSV upload directory

- **Table layout was actually my bug, not yours.** The bulk-delete update
  wrapped the raw `<table>` in a manual `<div class="table-responsive">`
  in the PHP view, *before* DataTables initializes on it. Your core's
  own working DataTables screens (see `assets/js/app.fn.js`,
  `initDatatable()`) never do this — they let DataTables generate its
  own `table-responsive` wrapper via the `dom` option, positioned only
  around the table itself, with search/pagination controls left outside
  it. Wrapping it manually first causes DataTables to measure column
  widths against the wrong container at init time, which is what
  visibly broke the layout. Fixed by removing the manual wrapper and
  adding the same `dom` pattern core already uses elsewhere.
- **CSV upload target directory hardened.** The temp file was being
  written to `sys_get_temp_dir()`. Shared hosting commonly restricts
  PHP's `open_basedir` to the site's own directory tree, which silently
  breaks `move_uploaded_file()` to the system temp folder. Switched to
  `FCPATH.'uploads/pm_tmp'` — the same directory tree core's own file
  uploads already write to successfully (see `Settings.php`'s logo
  upload) — with a fallback to the system temp dir if that path isn't
  writable for some reason, and a server error log entry if the move
  still fails so the real cause is visible next time.

## Question Bank changes from the previous update

- **Delete is now permanent.** Both single-question delete and the new
  bulk delete remove rows from `pm_question_bank` outright, rather than
  the old soft-delete (`status = 0`). This is safe for already-generated
  papers — `save_paper()` stores a full question snapshot in
  `pm_generated_papers.questions_json`, not a live reference, so deleting
  a bank question never breaks a paper you already saved. There is no
  undo, by design — this matches the explicit request for permanent
  deletion.
- **Bulk delete**: a checkbox column and "Select All" header checkbox
  were added to the grid, with a "Delete Selected" button that only
  enables once at least one row is checked. Selection is intentionally
  cleared on every page/filter change so the count never gets out of
  sync with what's actually checked.
- **CSV import rewrite**: the previous version used CodeIgniter's Upload
  library, whose MIME-type whitelist is a well-known source of false
  rejections for CSVs from different spreadsheet apps. It's now handled
  with plain `$_FILES` + extension checking instead. It also now strips
  a UTF-8 BOM if present — Excel's "CSV UTF-8" export adds one, which
  was silently corrupting the *first* column's header (`class_id` was
  becoming an unmatched key), so every imported row got `class_id = 0`
  and quietly vanished from every real class's filtered view. Rows with
  a `class_id`/`subject_id` that doesn't match a real class/subject in
  the branch are now skipped with a clear reason shown after upload,
  instead of either failing silently or importing invisible junk rows.

## Round 3 fixes: the real reason messages never showed, and CSV import polish

- **All flash messages were silently invisible — this is very likely
  the actual reason CSV import looked broken.** Your shared layout
  (`application/views/layout/index.php`) only displays alerts under the
  flashdata keys `alert-message-success` / `alert-message-error`, set
  via the `set_alert($type, $message)` helper. Every message in this
  addon — CSV upload results, template/paper delete confirmations, all
  of it — was calling `$this->session->set_flashdata('success', ...)` /
  `set_flashdata('error', ...)` with plain keys instead, which the
  layout never looks for. The import may well have been working,
  partially working, or failing outright at various points in this
  addon's history — there was simply no way to see which, because
  nothing ever displayed. Every call now goes through `set_alert()`.
- **A second, separate bug in the same area**: the layout renders that
  message directly into an unescaped, single-quoted JavaScript string
  (a SweetAlert toast — `title: '<?php echo $alert_message?>'`). A
  message containing a literal single quote (e.g. the old
  `class_id '999'` skip-reason wording) would have broken that toast's
  JavaScript outright, hiding the message a second, independent way even
  after the flashdata-key fix. Rewritten to avoid embedding quote
  characters in any dynamic message text.
- **Upfront CSV header validation added**, matching how your Student
  import validates its own template (`array_diff` against the expected
  column list) — uploading something that isn't the Paper Maker
  template now fails immediately with a clear "missing column(s): ..."
  message, rather than only surfacing as a wall of per-row skip reasons.
- The **"Download CSV Import Template"** link in the upload modal
  already points at a working, self-contained endpoint
  (`download_csv_template()`, which streams the template on the fly) —
  confirmed working, no change needed there.

## Round 4 fix: valid class_id/subject_id was always rejected

The alert-message fix from round 3 worked and finally surfaced the real
error: `class_id 8 does not match any class in this branch`, even for a
class that genuinely belongs to the branch. Cause: CI3's query builder
can return numeric DB columns as PHP strings depending on the driver,
but the CSV's `class_id`/`subject_id` were explicitly cast to `int`
before checking membership with `in_array(..., TRUE)` (strict mode) —
so `8 !== "8"` failed the check even though the id was completely valid.
Fixed by casting the DB-fetched id lists to int with `array_map('intval', ...)`
right after fetching them, so both sides of the comparison are the same
type. Verified against the exact reported scenario (class_id genuinely
in-branch, returned as a string by the DB) — now imports correctly.

## Round 5: manual total marks, question descriptions, per-type numbering, chapter filters

Run `sql/upgrade_question_description.sql` once (adds `pm_question_bank.description`, safe to re-run).

- **Manual total marks.** Both Manual Create and Auto Generator now have
  a "Total Marks" input next to Time Allowed. Leave it blank and the
  paper's total is auto-summed from the questions as before; enter a
  value and that's what gets saved and printed instead — useful for
  papers where the stated total doesn't match a literal sum (e.g.
  "attempt any 8 of 10").
- **Per-question description.** A new optional field, separate from both
  the question text and the solution/answer explanation — meant for a
  passage, case study, or extra context shown with the question. Added
  to the Add/Edit Question form, the Custom Question modal (Manual
  Create and Auto Generator), and as an optional CSV column (existing
  CSV files without it still import fine — it was deliberately left out
  of the required-header check). Renders as a small italic note under
  the question text on both the on-screen preview and the printed PDF.
- **Numbering restarts per question type.** The paper (and its answer
  key, which mirrors the same grouping and order) is now organized into
  sections — MCQs, then True/False, then Fill-in-the-Blanks, then Short,
  then Long, in that fixed order regardless of how the questions were
  originally added — each with its own heading and its own 1, 2, 3...
  numbering, rather than one continuous count across the whole paper.
  Verified with a mixed-order test set (MCQ/Short/MCQ/True-False/MCQ/Short)
  confirming both the question paper and answer key restart and group
  correctly, matched by original array position rather than by id (since
  custom/AI-added questions don't have a real bank id to match on).
- **Chapters page filters.** Added Class/Subject dropdowns and an Apply
  Filters button, matching Question Bank's style. Implemented as
  client-side show/hide rather than a server round-trip — chapter lists
  are small, so this keeps it instant with no new backend endpoint.
