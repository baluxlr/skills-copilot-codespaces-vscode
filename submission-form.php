<?php
/**
 * Deal submission form template
 */
defined('ABSPATH') || exit;

$sectors = DealRoom_Utilities::get_sectors();
$funding_stages = DealRoom_Utilities::get_funding_stages();
?>

<div class="dealroom-submission-wrapper">
    <form id="dealroom-submission-form" class="dealroom-submission-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('dealroom-submission-nonce', 'dealroom_nonce'); ?>
        <input type="hidden" name="post_id" id="post_id" value="<?php echo esc_attr($draft_id); ?>">
        
        <!-- Stepper Navigation -->
        <div class="dealroom-stepper">
            <div class="dealroom-step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title"><?php _e('Basic Info', 'dealroom-extension'); ?></div>
            </div>
            <div class="dealroom-step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title"><?php _e('Company Details', 'dealroom-extension'); ?></div>
            </div>
            <div class="dealroom-step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-title"><?php _e('Investment Details', 'dealroom-extension'); ?></div>
            </div>
            <div class="dealroom-step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-title"><?php _e('Documents & Media', 'dealroom-extension'); ?></div>
            </div>
            <div class="dealroom-step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-title"><?php _e('Review', 'dealroom-extension'); ?></div>
            </div>
        </div>
        
        <!-- Step 1: Basic Info -->
        <div class="dealroom-step-content" id="step-1">
            <h3><?php _e('Basic Information', 'dealroom-extension'); ?></h3>
            <p class="step-description"><?php _e('Start by providing some basic information about your investment opportunity.', 'dealroom-extension'); ?></p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="title"><?php _e('Project Title', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo isset($draft_data['title']) ? esc_attr($draft_data['title']) : ''; ?>" required>
                    <div class="description"><?php _e('A concise, attention-grabbing title for your investment opportunity.', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="organization_name"><?php _e('Organization/Company Name', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <input type="text" id="organization_name" name="organization_name" value="<?php echo isset($draft_data['organization_name']) ? esc_attr($draft_data['organization_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="sector"><?php _e('Industry/Sector', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <select id="sector" name="sector" required>
                        <option value=""><?php _e('-- Select Sector --', 'dealroom-extension'); ?></option>
                        <?php foreach ($sectors as $sector) : ?>
                            <option value="<?php echo esc_attr($sector); ?>" <?php selected(isset($draft_data['sector']) ? $draft_data['sector'] : '', $sector); ?>><?php echo esc_html($sector); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="location"><?php _e('Location', 'dealroom-extension'); ?></label>
                    <input type="text" id="location" name="location" value="<?php echo isset($draft_data['location']) ? esc_attr($draft_data['location']) : ''; ?>">
                    <div class="description"><?php _e('Where is your business headquartered? (City, Country)', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="content"><?php _e('Project Description', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <textarea id="content" name="content" rows="6" required><?php echo isset($draft_data['content']) ? esc_textarea($draft_data['content']) : ''; ?></textarea>
                    <div class="description"><?php _e('Provide a compelling overview of your project. What problem does it solve? What makes it unique?', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="tags"><?php _e('Tags', 'dealroom-extension'); ?></label>
                    <input type="text" id="tags" name="tags" value="<?php echo isset($draft_data['tags']) ? esc_attr($draft_data['tags']) : ''; ?>">
                    <div class="description"><?php _e('Add relevant keywords separated by commas to help investors find your deal.', 'dealroom-extension'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Company Details -->
        <div class="dealroom-step-content" id="step-2" style="display: none;">
            <h3><?php _e('Company Details', 'dealroom-extension'); ?></h3>
            <p class="step-description"><?php _e('Tell investors more about your company and team.', 'dealroom-extension'); ?></p>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="company_description"><?php _e('Company Description', 'dealroom-extension'); ?></label>
                    <textarea id="company_description" name="company_description" rows="5"><?php echo isset($draft_data['company_description']) ? esc_textarea($draft_data['company_description']) : ''; ?></textarea>
                    <div class="description"><?php _e('Provide background information about your company including founding date, mission, and vision.', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="featured_image"><?php _e('Project Featured Image', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <div class="file-upload-wrapper">
                        <div class="file-preview" id="featured_image_preview">
                            <?php if (isset($draft_data['featured_image_id']) && $draft_data['featured_image_id']) : ?>
                                <?php echo wp_get_attachment_image($draft_data['featured_image_id'], 'medium'); ?>
                            <?php else : ?>
                                <div class="upload-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span><?php _e('Click to upload image', 'dealroom-extension'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*" <?php echo !isset($draft_data['featured_image_id']) ? 'required' : ''; ?>>
                        <input type="hidden" id="featured_image_id" name="featured_image_id" value="<?php echo isset($draft_data['featured_image_id']) ? esc_attr($draft_data['featured_image_id']) : ''; ?>">
                    </div>
                    <div class="description"><?php _e('This is the main image that will represent your deal (JPEG, PNG, min 800x600px).', 'dealroom-extension'); ?></div>
                </div>
                
                <div class="form-group">
                    <label for="company_logo"><?php _e('Company Logo', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <div class="file-upload-wrapper">
                        <div class="file-preview" id="company_logo_preview">
                            <?php if (isset($draft_data['company_logo_id']) && $draft_data['company_logo_id']) : ?>
                                <?php echo wp_get_attachment_image($draft_data['company_logo_id'], 'thumbnail'); ?>
                            <?php else : ?>
                                <div class="upload-placeholder">
                                    <span class="dashicons dashicons-businessperson"></span>
                                    <span><?php _e('Click to upload logo', 'dealroom-extension'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="company_logo" name="company_logo" accept="image/*" <?php echo !isset($draft_data['company_logo_id']) ? 'required' : ''; ?>>
                        <input type="hidden" id="company_logo_id" name="company_logo_id" value="<?php echo isset($draft_data['company_logo_id']) ? esc_attr($draft_data['company_logo_id']) : ''; ?>">
                    </div>
                    <div class="description"><?php _e('Your company logo (square format recommended).', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="traction"><?php _e('Traction & Milestones', 'dealroom-extension'); ?></label>
                    <textarea id="traction" name="traction" rows="5"><?php echo isset($draft_data['traction']) ? esc_textarea($draft_data['traction']) : ''; ?></textarea>
                    <div class="description"><?php _e('Highlight key achievements, metrics, and milestones your company has reached.', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div id="team_members_container">
                <h4><?php _e('Team Members', 'dealroom-extension'); ?></h4>
                <div class="team-members">
                    <?php 
                    $team_members = isset($draft_data['team_members']) ? $draft_data['team_members'] : array('');
                    foreach ($team_members as $index => $member) : 
                    ?>
                        <div class="team-member">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="team_members[<?php echo $index; ?>][name]" placeholder="<?php _e('Name', 'dealroom-extension'); ?>" value="<?php echo isset($member['name']) ? esc_attr($member['name']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <input type="text" name="team_members[<?php echo $index; ?>][title]" placeholder="<?php _e('Title/Position', 'dealroom-extension'); ?>" value="<?php echo isset($member['title']) ? esc_attr($member['title']) : ''; ?>">
                                </div>
                                <?php if ($index > 0) : ?>
                                    <button type="button" class="remove-team-member button-secondary"><?php _e('Remove', 'dealroom-extension'); ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add_team_member" class="button-secondary"><?php _e('+ Add Team Member', 'dealroom-extension'); ?></button>
            </div>
        </div>
        
        <!-- Step 3: Investment Details -->
        <div class="dealroom-step-content" id="step-3" style="display: none;">
            <h3><?php _e('Investment Details', 'dealroom-extension'); ?></h3>
            <p class="step-description"><?php _e('Specify the financial aspects of your investment opportunity.', 'dealroom-extension'); ?></p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="funding_ask"><?php _e('Funding Ask (USD)', 'dealroom-extension'); ?> <span class="required">*</span></label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">$</span>
                        <input type="number" id="funding_ask" name="funding_ask" value="<?php echo isset($draft_data['funding_ask']) ? esc_attr($draft_data['funding_ask']) : ''; ?>" required>
                    </div>
                    <div class="description"><?php _e('Total amount of funding you are seeking.', 'dealroom-extension'); ?></div>
                </div>
                
                <div class="form-group">
                    <label for="funding_stage"><?php _e('Funding Stage', 'dealroom-extension'); ?></label>
                    <select id="funding_stage" name="funding_stage">
                        <option value=""><?php _e('-- Select Stage --', 'dealroom-extension'); ?></option>
                        <?php foreach ($funding_stages as $stage) : ?>
                            <option value="<?php echo esc_attr($stage); ?>" <?php selected(isset($draft_data['funding_stage']) ? $draft_data['funding_stage'] : '', $stage); ?>><?php echo esc_html($stage); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="minimum_investment"><?php _e('Minimum Investment (USD)', 'dealroom-extension'); ?></label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">$</span>
                        <input type="number" id="minimum_investment" name="minimum_investment" value="<?php echo isset($draft_data['minimum_investment']) ? esc_attr($draft_data['minimum_investment']) : ''; ?>">
                    </div>
                    <div class="description"><?php _e('Minimum amount an investor can contribute.', 'dealroom-extension'); ?></div>
                </div>
                
                <div class="form-group">
                    <label for="equity_offered"><?php _e('Equity Offered (%)', 'dealroom-extension'); ?></label>
                    <div class="input-with-suffix">
                        <input type="number" id="equity_offered" name="equity_offered" min="0" max="100" step="0.01" value="<?php echo isset($draft_data['equity_offered']) ? esc_attr($draft_data['equity_offered']) : ''; ?>">
                        <span class="input-suffix">%</span>
                    </div>
                    <div class="description"><?php _e('Percentage of equity being offered in exchange for the investment.', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div id="milestones_container">
                <h4><?php _e('Use of Funds & Milestones', 'dealroom-extension'); ?></h4>
                <div class="milestones">
                    <?php 
                    $milestones = isset($draft_data['milestones']) ? $draft_data['milestones'] : array('');
                    foreach ($milestones as $index => $milestone) : 
                    ?>
                        <div class="milestone">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="milestones[<?php echo $index; ?>][description]" placeholder="<?php _e('Milestone Description', 'dealroom-extension'); ?>" value="<?php echo isset($milestone['description']) ? esc_attr($milestone['description']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <div class="input-with-prefix">
                                        <span class="input-prefix">$</span>
                                        <input type="number" name="milestones[<?php echo $index; ?>][amount]" placeholder="<?php _e('Amount', 'dealroom-extension'); ?>" value="<?php echo isset($milestone['amount']) ? esc_attr($milestone['amount']) : ''; ?>">
                                    </div>
                                </div>
                                <?php if ($index > 0) : ?>
                                    <button type="button" class="remove-milestone button-secondary"><?php _e('Remove', 'dealroom-extension'); ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add_milestone" class="button-secondary"><?php _e('+ Add Milestone', 'dealroom-extension'); ?></button>
            </div>
        </div>
        
        <!-- Step 4: Documents & Media -->
        <div class="dealroom-step-content" id="step-4" style="display: none;">
            <h3><?php _e('Documents & Media', 'dealroom-extension'); ?></h3>
            <p class="step-description"><?php _e('Upload supporting documents and media to strengthen your pitch.', 'dealroom-extension'); ?></p>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="pitchdeck"><?php _e('Pitch Deck', 'dealroom-extension'); ?></label>
                    <div class="file-upload-wrapper">
                        <div class="file-preview" id="pitchdeck_preview">
                            <?php if (isset($draft_data['pitchdeck']) && $draft_data['pitchdeck']) : ?>
                                <div class="file-info">
                                    <span class="dashicons dashicons-media-document"></span>
                                    <span class="filename"><?php echo esc_html(basename($draft_data['pitchdeck'])); ?></span>
                                </div>
                            <?php else : ?>
                                <div class="upload-placeholder">
                                    <span class="dashicons dashicons-media-document"></span>
                                    <span><?php _e('Click to upload pitch deck', 'dealroom-extension'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="pitchdeck" name="pitchdeck" accept=".pdf,.ppt,.pptx">
                        <input type="hidden" id="pitchdeck_url" name="pitchdeck_url" value="<?php echo isset($draft_data['pitchdeck']) ? esc_attr($draft_data['pitchdeck']) : ''; ?>">
                    </div>
                    <div class="description"><?php _e('Upload your pitch deck (PDF, PPT, PPTX - max 10MB).', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="financial_model"><?php _e('Financial Model', 'dealroom-extension'); ?></label>
                    <div class="file-upload-wrapper">
                        <div class="file-preview" id="financial_model_preview">
                            <?php if (isset($draft_data['financial_model']) && $draft_data['financial_model']) : ?>
                                <div class="file-info">
                                    <span class="dashicons dashicons-media-spreadsheet"></span>
                                    <span class="filename"><?php echo esc_html(basename($draft_data['financial_model'])); ?></span>
                                </div>
                            <?php else : ?>
                                <div class="upload-placeholder">
                                    <span class="dashicons dashicons-media-spreadsheet"></span>
                                    <span><?php _e('Click to upload financial model', 'dealroom-extension'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="financial_model" name="financial_model" accept=".xls,.xlsx,.pdf">
                        <input type="hidden" id="financial_model_url" name="financial_model_url" value="<?php echo isset($draft_data['financial_model']) ? esc_attr($draft_data['financial_model']) : ''; ?>">
                    </div>
                    <div class="description"><?php _e('Upload your financial projections (Excel, PDF - max 10MB).', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="additional_docs"><?php _e('Additional Documents', 'dealroom-extension'); ?></label>
                    <div class="file-upload-wrapper">
                        <div class="file-preview" id="additional_docs_preview">
                            <?php if (isset($draft_data['additional_docs']) && $draft_data['additional_docs']) : ?>
                                <div class="file-info">
                                    <span class="dashicons dashicons-media-document"></span>
                                    <span class="filename"><?php echo esc_html(basename($draft_data['additional_docs'])); ?></span>
                                </div>
                            <?php else : ?>
                                <div class="upload-placeholder">
                                    <span class="dashicons dashicons-media-document"></span>
                                    <span><?php _e('Click to upload additional documents', 'dealroom-extension'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="additional_docs" name="additional_docs" accept=".pdf,.doc,.docx,.zip">
                        <input type="hidden" id="additional_docs_url" name="additional_docs_url" value="<?php echo isset($draft_data['additional_docs']) ? esc_attr($draft_data['additional_docs']) : ''; ?>">
                    </div>
                    <div class="description"><?php _e('Upload any additional supporting documents (PDF, DOC, ZIP - max 10MB).', 'dealroom-extension'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="video_url"><?php _e('Video URL', 'dealroom-extension'); ?></label>
                    <input type="url" id="video_url" name="video_url" value="<?php echo isset($draft_data['video_url']) ? esc_url($draft_data['video_url']) : ''; ?>">
                    <div class="description"><?php _e('Link to your pitch video (YouTube, Vimeo, etc.).', 'dealroom-extension'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Step 5: Review -->
        <div class="dealroom-step-content" id="step-5" style="display: none;">
            <h3><?php _e('Review Your Submission', 'dealroom-extension'); ?></h3>
            <p class="step-description"><?php _e('Review your information before submitting. You can go back to any step to make changes.', 'dealroom-extension'); ?></p>
            
            <div class="review-section">
                <h4><?php _e('Basic Information', 'dealroom-extension'); ?></h4>
                <div class="review-content" id="review-basic"></div>
            </div>
            
            <div class="review-section">
                <h4><?php _e('Company Details', 'dealroom-extension'); ?></h4>
                <div class="review-content" id="review-company"></div>
            </div>
            
            <div class="review-section">
                <h4><?php _e('Investment Details', 'dealroom-extension'); ?></h4>
                <div class="review-content" id="review-investment"></div>
            </div>
            
            <div class="review-section">
                <h4><?php _e('Documents & Media', 'dealroom-extension'); ?></h4>
                <div class="review-content" id="review-documents"></div>
            </div>
            
            <div class="terms-agreement">
                <label>
                    <input type="checkbox" id="terms_agreement" name="terms_agreement" required>
                    <?php _e('I confirm that all information provided is accurate and complete to the best of my knowledge. I understand that providing false or misleading information may result in rejection of my submission.', 'dealroom-extension'); ?>
                </label>
            </div>
        </div>
        
        <!-- Form Navigation -->
        <div class="form-navigation">
            <div class="nav-left">
                <button type="button" id="prev-step" class="button-secondary" style="display: none;"><?php _e('Previous', 'dealroom-extension'); ?></button>
            </div>
            
            <div class="nav-center">
                <button type="button" id="save-draft" class="button-secondary"><?php _e('Save Draft', 'dealroom-extension'); ?></button>
                <span id="save-status"></span>
            </div>
            
            <div class="nav-right">
                <button type="button" id="next-step" class="button-primary"><?php _e('Next', 'dealroom-extension'); ?></button>
                <button type="submit" id="submit-deal" class="button-primary" style="display: none;"><?php _e('Submit Deal', 'dealroom-extension'); ?></button>
            </div>
        </div>
    </form>
</div>