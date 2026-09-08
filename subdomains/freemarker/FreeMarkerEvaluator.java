import freemarker.template.*;
import java.io.*;
import java.util.*;

public class FreeMarkerEvaluator {
    public static void main(String[] args) {
        if (args.length == 0) {
            System.out.print("");
            return;
        }
        
        String templateStr = args[0];
        if ("--base64".equals(args[0]) && args.length > 1) {
            byte[] decoded = Base64.getDecoder().decode(args[1]);
            templateStr = new String(decoded, java.nio.charset.StandardCharsets.UTF_8);
        }

        try {
            Configuration cfg = new Configuration(Configuration.VERSION_2_3_32);
            cfg.setDefaultEncoding("UTF-8");
            cfg.setTemplateExceptionHandler(TemplateExceptionHandler.RETHROW_HANDLER);
            cfg.setLogTemplateExceptions(false);
            cfg.setWrapUncheckedExceptions(true);
            cfg.setNumberFormat("computer");
            
            // Rich Customer Feedback & Onboarding Survey Context
            Map<String, Object> data = new HashMap<>();
            
            Map<String, Object> user = new HashMap<>();
            user.put("name", "Alex Morgan");
            user.put("firstName", "Alex");
            user.put("lastName", "Morgan");
            user.put("email", "alex.morgan@apexsystems.internal");
            user.put("role", "VP of Customer Operations");
            user.put("company", "Apex Global Systems");
            user.put("loyaltyTier", "Platinum Enterprise");
            user.put("points", 18500);
            data.put("user", user);

            Map<String, Object> survey = new HashMap<>();
            survey.put("id", "FBK-2026-9042");
            survey.put("product", "PulseCloud Enterprise Suite");
            survey.put("status", "Verified & Dispatched");
            survey.put("score", "NPS 10/10 Promoter");
            survey.put("submittedAt", "September 2026");
            data.put("survey", survey);

            Map<String, Object> company = new HashMap<>();
            company.put("name", "PulseCloud Technologies");
            company.put("supportEmail", "feedback@pulsecloud.io");
            company.put("portalUrl", "https://portal.pulsecloud.io");
            company.put("discountVoucher", "NPS-VIP-THANKYOU-2026");
            data.put("company", company);

            Template t = new Template("feedback_receipt", new StringReader(templateStr), cfg);
            StringWriter out = new StringWriter();
            t.process(data, out);
            System.out.print(out.toString());
        } catch (Exception e) {
            System.out.print("FreeMarker Template Compilation Error: " + e.getMessage());
        }
    }
}
