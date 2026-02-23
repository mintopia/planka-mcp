<?php

use App\Mcp\Servers\PlankaServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', PlankaServer::class);
