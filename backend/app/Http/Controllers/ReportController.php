<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($user->role->roleName, ['Admin', 'Manager', 'IT Support', 'Employee'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildReportData($user),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = auth('api')->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reportData = $this->buildReportData($user);
        $html = $this->renderPdfHtml($reportData);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $filename = $this->buildExportFilename('pdf');

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user = auth('api')->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reportData = $this->buildReportData($user);
        $filename = $this->buildExportFilename('xlsx');
        $filePath = tempnam(sys_get_temp_dir(), 'helpdeskpro-report-');

        $writer = new XlsxWriter();
        $writer->openToFile($filePath);

        $this->writeOverviewSheet($writer, $reportData);
        $this->writeBreakdownSheet($writer, $reportData['statusBreakdown'], 'Status Breakdown', ['Status', 'Tickets']);
        $this->writeBreakdownSheet($writer, $reportData['priorityBreakdown'], 'Priority Breakdown', ['Priority', 'Tickets']);
        $this->writeBreakdownSheet($writer, $reportData['categoryBreakdown'], 'Category Breakdown', ['Category', 'Tickets']);
        $this->writeBreakdownSheet($writer, $reportData['monthlyTickets'], 'Monthly Tickets', ['Month', 'Tickets'], 'count', 'month');

        if (!empty($reportData['assignedAgents'])) {
            $this->writeBreakdownSheet($writer, $reportData['assignedAgents'], 'Assigned Agents', ['Agent', 'Tickets']);
        }

        $this->writeRecentTicketsSheet($writer, $reportData['recentTickets']);

        $writer->close();

        return response()->streamDownload(function () use ($filePath) {
            readfile($filePath);
            @unlink($filePath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildReportData($user): array
    {
        $tickets = $this->buildScopedTicketQuery($user)
            ->with([
                'category:id,categoryName',
                'creator:id,fullName',
                'assignedUser:id,fullName',
            ])
            ->orderByDesc('createdAt')
            ->get();

        return [
            'scope' => $this->buildScopeMeta($user),
            'generatedAt' => now()->toIso8601String(),
            'summary' => $this->buildSummary($tickets),
            'statusBreakdown' => $this->buildStatusBreakdown($tickets),
            'priorityBreakdown' => $this->buildPriorityBreakdown($tickets),
            'categoryBreakdown' => $this->buildCategoryBreakdown($tickets),
            'monthlyTickets' => $this->buildMonthlyTickets($tickets),
            'averageResolutionTime' => $this->averageResolutionTime($tickets),
            'assignedAgents' => $this->buildAssignedAgentBreakdown($tickets, $user),
            'recentTickets' => $this->buildRecentTickets($tickets),
        ];
    }

    private function buildScopedTicketQuery($user)
    {
        $query = Ticket::query();

        return match ($user->role->roleName) {
            'Admin' => $query,
            'Manager' => $query,
            'IT Support' => $query->where('assignedTo', $user->id),
            'Employee' => $query->where('createdBy', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function buildScopeMeta($user): array
    {
        return match ($user->role->roleName) {
            'Admin' => [
                'role' => 'Admin',
                'label' => 'System-wide reports',
                'description' => 'Live ticket analytics across the full HelpDeskPro database.',
            ],
            'Manager' => [
                'role' => 'Manager',
                'label' => 'System-wide reports',
                'description' => 'Live ticket analytics across the full HelpDeskPro database.',
            ],
            'IT Support' => [
                'role' => 'IT Support',
                'label' => 'Assigned ticket reports',
                'description' => 'Live ticket analytics for the tickets assigned to you.',
            ],
            'Employee' => [
                'role' => 'Employee',
                'label' => 'My ticket reports',
                'description' => 'Live ticket analytics for the tickets you created.',
            ],
            default => [
                'role' => $user->role->roleName,
                'label' => 'Reports',
                'description' => 'Live ticket analytics from the database.',
            ],
        };
    }

    private function buildSummary($tickets): array
    {
        return [
            'totalTickets' => $tickets->count(),
            'openTickets' => $tickets->where('status', 'Open')->count(),
            'assignedTickets' => $tickets->where('status', 'Assigned')->count(),
            'inProgressTickets' => $tickets->where('status', 'In Progress')->count(),
            'resolvedTickets' => $tickets->where('status', 'Resolved')->count(),
            'closedTickets' => $tickets->where('status', 'Closed')->count(),
        ];
    }

    private function buildStatusBreakdown($tickets): array
    {
        $statuses = ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'];

        return collect($statuses)
            ->map(function (string $status) use ($tickets) {
                return [
                    'name' => $status,
                    'value' => $tickets->where('status', $status)->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildPriorityBreakdown($tickets): array
    {
        $priorities = ['Low', 'Medium', 'High', 'Critical', 'Urgent'];
        $knownPriorities = collect($priorities);
        $ticketPriorities = $tickets->pluck('priority')->filter()->unique()->values();

        $orderedPriorities = $knownPriorities
            ->merge($ticketPriorities->reject(fn ($priority) => $knownPriorities->contains($priority)))
            ->values();

        return $orderedPriorities
            ->map(function (string $priority) use ($tickets) {
                return [
                    'name' => $priority,
                    'value' => $tickets->where('priority', $priority)->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildCategoryBreakdown($tickets): array
    {
        return $tickets
            ->groupBy(fn ($ticket) => $ticket->category?->categoryName ?? 'Uncategorized')
            ->map(function ($group, string $categoryName) {
                return [
                    'name' => $categoryName,
                    'value' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    private function buildMonthlyTickets($tickets): array
    {
        $currentYear = now()->year;

        return collect(range(1, 12))
            ->map(function (int $month) use ($tickets, $currentYear) {
                return [
                    'month' => Carbon::create($currentYear, $month, 1)->format('M'),
                    'count' => $tickets->filter(function ($ticket) use ($month, $currentYear) {
                        return $ticket->createdAt
                            && $ticket->createdAt->year === $currentYear
                            && $ticket->createdAt->month === $month;
                    })->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function averageResolutionTime($tickets): float
    {
        $resolvedTickets = $tickets->filter(function ($ticket) {
            return in_array($ticket->status, ['Resolved', 'Closed'], true)
                && $ticket->createdAt
                && $ticket->closedAt;
        });

        if ($resolvedTickets->isEmpty()) {
            return 0;
        }

        $totalHours = $resolvedTickets->sum(function ($ticket) {
            return $ticket->createdAt->diffInMinutes($ticket->closedAt) / 60;
        });

        return round($totalHours / $resolvedTickets->count(), 2);
    }

    private function buildAssignedAgentBreakdown($tickets, $user): array
    {
        $allowedRoles = ['Admin', 'Manager'];

        if (!in_array($user->role->roleName, $allowedRoles, true)) {
            return [];
        }

        return $tickets
            ->filter(fn ($ticket) => $ticket->assignedTo)
            ->groupBy(function ($ticket) {
                return $ticket->assignedUser?->id ?? $ticket->assignedTo;
            })
            ->map(function ($group) {
                $ticket = $group->first();
                $name = $ticket->assignedUser?->fullName
                    ?? $ticket->assignedSupportName
                    ?? ($ticket->assignedTo ? 'User #' . $ticket->assignedTo : 'Unassigned');

                return [
                    'name' => $name,
                    'value' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    private function buildRecentTickets($tickets): array
    {
        return $tickets
            ->take(10)
            ->values()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticketNumber' => $ticket->ticketNumber,
                    'title' => $ticket->title,
                    'category' => $ticket->category?->categoryName ?? 'Uncategorized',
                    'creator' => $ticket->creator?->fullName ?? 'Unknown',
                    'assignedTo' => $ticket->assignedUser?->fullName ?? $ticket->assignedSupportName,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'createdAt' => $ticket->createdAt?->toIso8601String(),
                    'closedAt' => $ticket->closedAt?->toIso8601String(),
                ];
            })
            ->all();
    }

    private function renderPdfHtml(array $reportData): string
    {
        $generatedAt = Carbon::parse($reportData['generatedAt'])->format('Y-m-d H:i:s');
        $summary = $reportData['summary'];

        $statusRows = $this->renderKeyValueRows($reportData['statusBreakdown']);
        $priorityRows = $this->renderKeyValueRows($reportData['priorityBreakdown']);
        $categoryRows = $this->renderKeyValueRows($reportData['categoryBreakdown']);
        $monthlyRows = $this->renderKeyValueRows($reportData['monthlyTickets'], 'month', 'count');

        $recentRows = '';
        foreach ($reportData['recentTickets'] as $ticket) {
            $recentRows .= '<tr>'
                . '<td>' . e($ticket['ticketNumber']) . '</td>'
                . '<td>' . e($ticket['title']) . '</td>'
                . '<td>' . e($ticket['category']) . '</td>'
                . '<td>' . e($ticket['creator']) . '</td>'
                . '<td>' . e($ticket['assignedTo'] ?? 'Unassigned') . '</td>'
                . '<td>' . e($ticket['priority']) . '</td>'
                . '<td>' . e($ticket['status']) . '</td>'
                . '</tr>';
        }

        return '<!doctype html><html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,Arial,sans-serif;color:#0f172a;font-size:12px;}'
            . 'h1,h2,h3{margin:0 0 8px 0;} .muted{color:#64748b;} .section{margin-top:18px;}'
            . '.grid{display:flex;flex-wrap:wrap;gap:10px;} .card{border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;min-width:160px;}'
            . '.card .label{font-size:10px;text-transform:uppercase;color:#64748b;} .card .value{font-size:18px;font-weight:bold;margin-top:4px;}'
            . 'table{width:100%;border-collapse:collapse;margin-top:8px;} th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:left;vertical-align:top;} th{background:#f8fafc;}'
            . '</style></head><body>'
            . '<h1>HelpDeskPro Reports</h1>'
            . '<div class="muted">Generated at ' . e($generatedAt) . '</div>'
            . '<div class="section"><h2>Scope</h2><div>' . e($reportData['scope']['label']) . '</div><div class="muted">' . e($reportData['scope']['description']) . '</div></div>'
            . '<div class="section"><h2>KPI Statistics</h2><div class="grid">'
            . $this->pdfCard('Total Tickets', $summary['totalTickets'])
            . $this->pdfCard('Open', $summary['openTickets'])
            . $this->pdfCard('Assigned', $summary['assignedTickets'])
            . $this->pdfCard('In Progress', $summary['inProgressTickets'])
            . $this->pdfCard('Resolved', $summary['resolvedTickets'])
            . $this->pdfCard('Closed', $summary['closedTickets'])
            . $this->pdfCard('Avg. Resolution (h)', number_format((float) $reportData['averageResolutionTime'], 2))
            . '</div></div>'
            . '<div class="section"><h2>Status Statistics</h2><table><thead><tr><th>Status</th><th>Tickets</th></tr></thead><tbody>' . $statusRows . '</tbody></table></div>'
            . '<div class="section"><h2>Priority Statistics</h2><table><thead><tr><th>Priority</th><th>Tickets</th></tr></thead><tbody>' . $priorityRows . '</tbody></table></div>'
            . '<div class="section"><h2>Category Statistics</h2><table><thead><tr><th>Category</th><th>Tickets</th></tr></thead><tbody>' . $categoryRows . '</tbody></table></div>'
            . '<div class="section"><h2>Monthly Volume</h2><table><thead><tr><th>Month</th><th>Tickets</th></tr></thead><tbody>' . $monthlyRows . '</tbody></table></div>'
            . '<div class="section"><h2>Recent Tickets</h2><table><thead><tr><th>Ticket #</th><th>Title</th><th>Category</th><th>Created By</th><th>Assigned To</th><th>Priority</th><th>Status</th></tr></thead><tbody>' . $recentRows . '</tbody></table></div>'
            . '</body></html>';
    }

    private function pdfCard(string $label, $value): string
    {
        return '<div class="card"><div class="label">' . e($label) . '</div><div class="value">' . e((string) $value) . '</div></div>';
    }

    private function renderKeyValueRows(array $items, string $nameKey = 'name', string $valueKey = 'value'): string
    {
        return collect($items)
            ->map(function (array $item) use ($nameKey, $valueKey) {
                return '<tr><td>' . e((string) ($item[$nameKey] ?? '-')) . '</td><td>' . e((string) ($item[$valueKey] ?? 0)) . '</td></tr>';
            })
            ->implode('');
    }

    private function writeOverviewSheet($writer, array $reportData): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Overview');

        $writer->addRow(Row::fromValues(['HelpDeskPro Reports']));
        $writer->addRow(Row::fromValues(['Generated At', Carbon::parse($reportData['generatedAt'])->format('Y-m-d H:i:s')]));
        $writer->addRow(Row::fromValues(['Scope', $reportData['scope']['label']]));
        $writer->addRow(Row::fromValues(['Description', $reportData['scope']['description']]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['KPI', 'Value']));

        foreach ($reportData['summary'] as $label => $value) {
            $writer->addRow(Row::fromValues([$this->labelizeMetric($label), $value]));
        }

        $writer->addRow(Row::fromValues(['Average Resolution Time (hours)', number_format((float) $reportData['averageResolutionTime'], 2)]));
    }

    private function writeBreakdownSheet($writer, array $rows, string $sheetName, array $headers, string $valueKey = 'value', string $nameKey = 'name'): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName($sheetName);
        $writer->addRow(Row::fromValues($headers));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues([
                $row[$nameKey] ?? '-',
                $row[$valueKey] ?? 0,
            ]));
        }
    }

    private function writeRecentTicketsSheet($writer, array $recentTickets): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Recent Tickets');
        $writer->addRow(Row::fromValues(['Ticket #', 'Title', 'Category', 'Created By', 'Assigned To', 'Priority', 'Status', 'Created At', 'Closed At']));

        foreach ($recentTickets as $ticket) {
            $writer->addRow(Row::fromValues([
                $ticket['ticketNumber'] ?? '-',
                $ticket['title'] ?? '-',
                $ticket['category'] ?? '-',
                $ticket['creator'] ?? '-',
                $ticket['assignedTo'] ?? 'Unassigned',
                $ticket['priority'] ?? '-',
                $ticket['status'] ?? '-',
                $ticket['createdAt'] ?? '-',
                $ticket['closedAt'] ?? '-',
            ]));
        }
    }

    private function labelizeMetric(string $metric): string
    {
        return match ($metric) {
            'totalTickets' => 'Total Tickets',
            'openTickets' => 'Open Tickets',
            'assignedTickets' => 'Assigned Tickets',
            'inProgressTickets' => 'In Progress Tickets',
            'resolvedTickets' => 'Resolved Tickets',
            'closedTickets' => 'Closed Tickets',
            default => ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $metric)),
        };
    }

    private function buildExportFilename(string $extension): string
    {
        return 'helpdeskpro-reports-' . now()->format('Ymd-His') . '.' . $extension;
    }
}