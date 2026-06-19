<?php
namespace App\Enums;

enum ActionType: string
{
    case Created             = 'created';
    case Assigned            = 'assigned';
    case StatusChanged       = 'status_changed';
    case Commented           = 'commented';
    case AttachmentUploaded  = 'attachment_uploaded';
    case Completed           = 'completed';
    case Cancelled           = 'cancelled';
    case Edited              = 'edited';
    case Reassigned          = 'reassigned';
}
