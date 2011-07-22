<div class="PhorumNavBlock">
  <span class="PhorumNavHeading">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{INCLUDE loginout_menu}
</div>

<div class="PhorumNavBlock" style="padding:10px">
  <h1 style="margin: 5px 0px 15px 0px">
    {LANG->mod_google_maps->UserMapTitle}
  </h1>
  {MOD_GOOGLE_MAPS}
</div>

