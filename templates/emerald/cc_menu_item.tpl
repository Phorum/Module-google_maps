{IF LOCATION_PANEL_ACTIVE}
  {VAR MENU_ITEM_CLASS 'class="current"'}
{ELSE}
  {VAR MENU_ITEM_CLASS ""}
{/IF}

<li>
  <a {MENU_ITEM_CLASS} href="{URL->CC_LOCATION}">
    {LANG->mod_google_maps->CCMenuItem}
  </a>
</li>

