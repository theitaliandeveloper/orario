<?php
function schema_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1"
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function legacy_schema_detected(mysqli $conn): bool
{
    $legacyTables = schema_table_exists($conn, 'classes_legacy')
        && schema_table_exists($conn, 'subjects_legacy')
        && schema_table_exists($conn, 'timetable_legacy');

    $oldTables = schema_table_exists($conn, 'classes')
        && schema_table_exists($conn, 'subjects')
        && schema_table_exists($conn, 'timetable');

    return ($legacyTables || $oldTables) && !schema_table_exists($conn, 'timetable_slots');
}

function normalized_schema_detected(mysqli $conn): bool
{
    return schema_table_exists($conn, 'classes')
        && schema_table_exists($conn, 'subjects')
        && schema_table_exists($conn, 'timetable_slots')
        && schema_table_exists($conn, 'timetable_lessons');
}
