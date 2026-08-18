"""Remove orphaned ugc-card blocks that were left after the dynamic container was inserted."""
import re

with open('public/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Find and remove all ugc-card divs that are orphans (outside ugcGrid)
# The ugcGrid div now has id="ugcGrid" with a comment inside, followed by orphaned cards

# Strategy: Find the close of ugcGrid and remove the orphaned cards block up to the next section
# Pattern: </div> [newline] [newline] <div class="ugc-card"> ... up to a non-ugc div

# Count ugc-card occurrences
ugc_count = content.count('<div class="ugc-card">')
print("Orphaned ugc-card blocks found:", ugc_count)

if ugc_count > 0:
    # Remove all orphaned ugc-card blocks
    # They appear as a series of ugc-card divs after the closing </div> of ugcGrid
    # Find position of ugcGrid
    ugc_grid_end = content.find('</div>\n\n    </div>', content.find('id="ugcGrid"'))
    if ugc_grid_end < 0:
        ugc_grid_end = content.find('</div>\n\n      <div class="ugc-card">')
    
    # Simpler: just use regex to remove all standalone ugc-card blocks
    # Remove pattern: blank line + <div class="ugc-card">...</div> + blank line
    # Use a more targeted approach: find the section between ugcGrid closing and next section header

    start_orphan = content.find('\n      <div class="ugc-card">')
    if start_orphan < 0:
        start_orphan = content.find('\n    <div class="ugc-card">')
    
    if start_orphan > 0:
        # Find the end of all orphaned ugc blocks - look for next section div that's not ugc
        end_orphan = content.find('\n    <!-- SECCIÓN:', start_orphan)
        if end_orphan < 0:
            end_orphan = content.find('\n    <div class="grid-2-col">', start_orphan)
        if end_orphan < 0:
            end_orphan = content.find('\n    <section', start_orphan)
        
        if end_orphan > 0:
            removed = content[start_orphan:end_orphan]
            print("Removing %d chars of orphaned content" % len(removed))
            print("First 100 chars:", removed[:100])
            content = content[:start_orphan] + content[end_orphan:]
            print("After removal, ugc-card count:", content.count('<div class="ugc-card">'))
        else:
            print("Could not find end of orphaned block, manual fix needed")
    else:
        print("No orphaned ugc-card blocks found (already clean)")

with open('public/index.html', 'w', encoding='utf-8') as f:
    f.write(content)

# Final checks
checks = {
    'ugcGrid': 'id="ugcGrid"',
    'kpiMetaVisualizaciones': 'id="kpiMetaVisualizaciones"',
    'kpiTiktokVistas': 'id="kpiTiktokVistas"',
    'kpiTiktokSub': 'id="kpiTiktokSub"',
    'dashboardLoader': 'id="dashboardLoader"',
    'dashboard-engine': 'dashboard-engine.js',
    'timelineContainer': 'id="timelineContainer"',
    'dashLema': 'id="dashLema"',
}
print("\n=== Final verification ===")
for name, pattern in checks.items():
    print("  %s: %s" % (name, "OK" if pattern in content else "MISSING"))
print("Total ugc-card blocks remaining:", content.count('<div class="ugc-card">'))
