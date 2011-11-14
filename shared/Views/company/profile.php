<?php
use System\Web\Utils\HTMLUtils;
echo '<div class="company-profile ' . $mode . '">';
echo '<h2>' . __('company.profile.title', 'Company Profile') . '</h2>';
echo '<div class="company_logo">';
echo '<img src="' . $this->getController()->getCompanyLogo($row) . '" alt="Company Logo"/>';
echo '</div>';
echo '<div class="company_detail">';
echo '<div>' . __('company.company_name', 'Company Name :') . $row->company_name . '</div>';
if ($row->company_primary_url) {
	echo '<div>' . __('company.company_primary_url', 'Company Website :') . $row->company_primary_url . '</div>';
}
echo '<div>';
echo $this->getController()->renderContent('bottom-simple', array(
				'company_id' => $row->company_id));
if ($mode !== 'simple') {
	echo $this->getController()->renderContent('bottom-' . $mode, array(
					'company_id' => $row->company_id));
}
echo '</div>';
if ($mode === 'simple') {
	echo HTMLUtils::renderLink(\URLHelper::add(APP_URL, 'company/profile', 'id=' . $row->company_id), 'View Full Profile', array(
			'target' => '__blank'));
}
echo '</div>';
echo '</div>';
