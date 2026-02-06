"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CheckCircle2, XCircle, List } from "lucide-react";

interface Service {
  category: string;
  description: string;
  included: boolean;
}

interface ServiceListProps {
  services: Service[];
}

export function ServiceList({ services }: ServiceListProps) {
  const includedServices = services.filter((s) => s.included);
  const excludedServices = services.filter((s) => !s.included);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <List className="h-5 w-5" />
          Covered Services
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {includedServices.length === 0 && excludedServices.length === 0 ? (
          <div className="text-center py-8 text-muted-foreground">
            <p>No specific service restrictions</p>
            <p className="text-sm mt-1">All services are covered under this plan</p>
          </div>
        ) : (
          <>
            {/* Included Services */}
            {includedServices.length > 0 && (
              <div className="space-y-3">
                <div className="flex items-center gap-2 text-sm font-medium text-green-600">
                  <CheckCircle2 className="h-4 w-4" />
                  Included Services ({includedServices.length})
                </div>
                <div className="space-y-2">
                  {includedServices.map((service, index) => (
                    <div
                      key={index}
                      className="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg"
                    >
                      <CheckCircle2 className="h-4 w-4 text-green-600 mt-0.5 flex-shrink-0" />
                      <div className="flex-1 min-w-0">
                        <div className="font-medium text-sm">{service.category}</div>
                        {service.description && (
                          <div className="text-xs text-muted-foreground mt-0.5">{service.description}</div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Excluded Services */}
            {excludedServices.length > 0 && (
              <div className="space-y-3 pt-4 border-t">
                <div className="flex items-center gap-2 text-sm font-medium text-red-600">
                  <XCircle className="h-4 w-4" />
                  Not Included ({excludedServices.length})
                </div>
                <div className="space-y-2">
                  {excludedServices.map((service, index) => (
                    <div
                      key={index}
                      className="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg"
                    >
                      <XCircle className="h-4 w-4 text-red-600 mt-0.5 flex-shrink-0" />
                      <div className="flex-1 min-w-0">
                        <div className="font-medium text-sm">{service.category}</div>
                        {service.description && (
                          <div className="text-xs text-muted-foreground mt-0.5">{service.description}</div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </>
        )}

        {/* Service Categories Badge List (if no detailed services) */}
        {includedServices.length === 0 && excludedServices.length === 0 && services.length === 0 && (
          <div className="flex flex-wrap gap-2 pt-2">
            {[
              "Website Maintenance",
              "Bug Fixes",
              "Security Updates",
              "Performance Optimization",
              "Content Updates",
              "Technical Support",
            ].map((category) => (
              <Badge key={category} variant="secondary">
                {category}
              </Badge>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
