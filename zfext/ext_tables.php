<?php
if (TYPO3_MODE == 'BE') {
    $dispatcher = 'Zfext_Module';
    if (is_array($TBE_MODULES['_dispatcher'])) {
        array_unshift($TBE_MODULES['_dispatcher'], $dispatcher);
    } else {
        $TBE_MODULES['_dispatcher'] = array($dispatcher);
    }
}