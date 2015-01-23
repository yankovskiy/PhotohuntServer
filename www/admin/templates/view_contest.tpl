{include file="header.tpl"}
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}">К списку конкурсов</a><br><br>
<table border="1" cellpadding="5">
<tr>
<td>Фото</td>
<td>Тема следующего конкурса</td>
<td>Автор</td>
<td>Количество голосов</td>
<td>Проголосовавшие</td>
<td>Действие</td>
</tr>

{section name=img loop=$images}
<tr>
<td><a href="http://{$smarty.server.SERVER_NAME}/images/{$images[img].image.id}.jpg"><img src="http://{$smarty.server.SERVER_NAME}/images/{$images[img].image.id}.jpg" width="30%" height="30%"></a></td>
<td>{$images[img].image.subject}</td>
<td>{$images[img].image.display_name}</td>
<td>{$images[img].image.vote_count}</td>
<td>
{section name=vote loop=$images[img].votes}
{$images[img].votes[vote].display_name} ({$images[img].votes[vote].user_id} : <a href="http://whatismyipaddress.com/ip/{$images[img].votes[vote].from}" target="_blank">{$images[img].votes[vote].from}</a>)<br>
{/section}
</td>
<td>
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?contest={$images[img].image.contest_id}&id={$images[img].image.id}&type=image&action=edit">Редактировать</a> |
<a href="http://{$smarty.server.SERVER_NAME}{$SCRIPT_NAME}?contest={$images[img].image.contest_id}&id={$images[img].image.id}&type=image&action=delete">Удалить</a>
</td>
</tr>
{/section}



</table>

{include file="footer.tpl"}