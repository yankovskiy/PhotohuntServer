{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}">К списку конкурсов</a><br><br>
{$contest.id}. {$contest.subject}<br>
<table border="1" cellpadding="5">
<tr>
<td>Фото</td>
<td>Тема следующего конкурса</td>
<td>Автор</td>
<td>Количество голосов</td>
<td>Должен победить</td>
<td>Проголосовавшие</td>
<td>Exif</td>
<td>Действие</td>
</tr>

{section name=img loop=$images}
<tr>
<td><a href="http://{$smarty.server.SERVER_NAME}/images/{$images[img].image.id}.jpg"><img src="http://{$smarty.server.SERVER_NAME}/images/{$images[img].image.id}.jpg" width="30%" height="30%"></a></td>
<td>{$images[img].image.subject}</td>
<td>{$images[img].image.display_name}</td>
<td>{$images[img].image.vote_count}</td>
<td>
{if $images[img].image.must_win eq 0}
    Не задано
{elseif $images[img].image.must_win eq 1}
    Да
{/if}
</td>
<td>
{section name=vote loop=$images[img].votes}
{$images[img].votes[vote].display_name} ({$images[img].votes[vote].user_id} : <a href="http://whatismyipaddress.com/ip/{$images[img].votes[vote].from}" target="_blank">{$images[img].votes[vote].from}</a>)<br>
{/section}
</td>
<td>{$images[img].image.exif}</td>
<td>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?contest={$images[img].image.contest_id}&id={$images[img].image.id}&type=image&action=edit">Редактировать</a> |
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?contest={$images[img].image.contest_id}&id={$images[img].image.id}&type=image&action=delete" onclick="return confirm('Удалить?');">Удалить</a>
</td>
</tr>
{/section}
</table>
<br>
<br>
<strong>Загрузка изображения в конкурс</strong><br>
<form method="POST" action="{$SCRIPT_NAME}?action=add&type=image&contest={$contestId}" enctype="multipart/form-data">
<table border="1" cellpadding="2">
<tr>
<td>Тема следующего конкурса</td>
<td>Пользователь</td>
<td colspan="2">Файл</td>
</tr>
<tr>
<td><input type="text" name="subject" value=""></td>
<td><select name="user_id">{html_options values=$user_option_values selected=$user_option_selected output=$user_option_output}</select></td>
<td><input type="file" name="image" value="Обзор"></td>
<td><input type="submit" name="submit" value="Загрузить"></td>
</tr>
</table>
</form>

{include file="footer.tpl"}