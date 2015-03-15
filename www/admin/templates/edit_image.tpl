{include file="header.tpl"}

<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?id={$image.contest_id}&type=contest&action=view">К конкурсу</a><br><br>
<form method="POST" action="{$SCRIPT_NAME}?id={$image.id}&action=edit&type=image&contest={$image.contest_id}">
<table border=0 cellpadding=5>
<tr>
<td>Тема</td>
<td>Автор</td>
<td>Количество голосов</td>
<td>Должен победить</td>
</tr>
<tr>
<td><input type="text" name="subject" value="{$image.subject}"></td>
<td><select name="user_id">{html_options values=$user_option_values selected=$user_option_selected output=$user_option_output}</select></td>
<td><input type="text" name="vote_count" value="{$image.vote_count}"></td>
<td><select name="must_win">{html_options values=$must_win_option_values selected=$must_win_option_selected output=$must_win_option_output}</select></td>
</tr>
<tr><td colspan=4 align=center><input type="submit" name="submit" value="OK"></td>
</table>
</form>

{include file="footer.tpl"}