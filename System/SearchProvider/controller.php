<?php
class TSearchProviderController implements ISearchProvider {
	private $_appOwner;
	private $_ctl;
	function __construct(ISearchEngine $se) {
		$this->_appOwner = $se->getAppOwner();
	}
	function search($s, $config) {
		$this->_ctl = $this->_appOwner->getController ( $config ["controller"] );
		if ($this->_ctl instanceof ISearchProvider) {
			return $this->_ctl->search( $s,$config);
		}else{
			if (CGAF_DEBUG) {
				throw new SystemException('invalid instance '.$config ["controller"].' not inherits from ISearchProvider');
			}
		}
	}
/* (non-PHPdoc)
	 * @see ISearchProvider::name()
	 */
	public function name() {
		// TODO Auto-generated method stub
		
	}

}