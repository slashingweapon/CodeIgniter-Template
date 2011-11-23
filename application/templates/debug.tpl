{$assigned_vars|debug_var}

{if isset($template_name)}
	{$template_name|debug_var}
{/if}

{if !empty($config_vars)}
	{$config_vars|debug_var}
{/if}

{if !empty($template_data)}
	{$template_data|debug_var}
{/if}

