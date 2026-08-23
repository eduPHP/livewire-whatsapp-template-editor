<?php

namespace WaTemplates\Enums;

enum ButtonType: string
{
    case QuickReply = 'QUICK_REPLY';
    case Url = 'URL';
    case PhoneNumber = 'PHONE_NUMBER';
    case CopyCode = 'COPY_CODE';
    case Mpm = 'MPM';
    case Spm = 'SPM';
}
