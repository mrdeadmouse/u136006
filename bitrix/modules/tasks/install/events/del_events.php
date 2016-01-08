<?
$DB->Query("DELETE FROM b_event_type WHERE EVENT_NAME in (
	'TASK_REMINDER'
)");

$DB->Query("DELETE FROM b_event_message WHERE EVENT_NAME in (
	'TASK_REMINDER'
)");
?>