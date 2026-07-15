<?php

namespace App\Enums;

enum CorruptionReportStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case NeedsMoreInfo = 'needs_more_info';
    case EditorApproved = 'editor_approved';
    case LegalApproved = 'legal_approved';
    case Published = 'published';
    case Rejected = 'rejected';
}
