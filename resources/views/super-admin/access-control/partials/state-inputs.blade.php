<input type="hidden" name="tab" :value="activeTab">
<input type="hidden" name="state_user_id" :value="selectedUser">
<input type="hidden" name="user_q" value="{{ $userSearch ?? '' }}">
<input type="hidden" name="user_per_page" value="{{ $userPerPage ?? 10 }}">
<input type="hidden" name="user_filter" value="{{ $userFilter ?? 'all' }}">
<input type="hidden" name="module_filter" value="{{ $moduleFilter ?? 'all' }}">
