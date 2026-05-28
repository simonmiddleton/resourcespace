<?php 

ps_query("UPDATE preview_size SET width = 140, height = 140 WHERE id = 'col' AND width < 140 AND height < 140");